<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): static
    {
        return $this->withToken(User::factory()->create()->createToken('test')->plainTextToken);
    }

    public function test_index_lists_projects(): void
    {
        Project::factory()->count(3)->create();

        $this->withKey()->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_can_filter_by_client(): void
    {
        $mine = Client::factory()->create();
        Project::factory()->count(2)->create(['client_id' => $mine->id]);
        Project::factory()->count(3)->create();

        $this->withKey()->getJson("/api/v1/projects?client_id={$mine->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_store_creates_a_project_and_defaults_to_active(): void
    {
        $client = Client::factory()->create();

        $this->withKey()->postJson('/api/v1/projects', [
            'client_id' => $client->id,
            'title' => 'Website Relaunch',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Website Relaunch')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_store_accepts_an_explicit_status(): void
    {
        $client = Client::factory()->create();

        $this->withKey()->postJson('/api/v1/projects', [
            'client_id' => $client->id,
            'title' => 'Paused work',
            'status' => 'paused',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'paused');
    }

    public function test_store_rejects_an_unknown_client(): void
    {
        $this->withKey()->postJson('/api/v1/projects', [
            'client_id' => 999,
            'title' => 'Orphan',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('client_id');
    }

    public function test_store_rejects_an_invalid_status(): void
    {
        $client = Client::factory()->create();

        $this->withKey()->postJson('/api/v1/projects', [
            'client_id' => $client->id,
            'title' => 'Bad status',
            'status' => 'archived',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_show_returns_a_project_with_totals(): void
    {
        $project = Project::factory()->create(['title' => 'Website']);

        $this->withKey()->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Website')
            ->assertJsonPath('data.total_seconds', 0);
    }

    public function test_update_patches_only_the_given_fields(): void
    {
        $project = Project::factory()->create([
            'title' => 'Old',
            'status' => ProjectStatus::Active,
        ]);

        $this->withKey()->patchJson("/api/v1/projects/{$project->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Old')
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_destroy_deletes_a_project(): void
    {
        $project = Project::factory()->create();

        $this->withKey()->deleteJson("/api/v1/projects/{$project->id}")->assertNoContent();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
