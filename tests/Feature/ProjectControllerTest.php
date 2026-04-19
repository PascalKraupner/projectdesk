<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/projects')
            ->assertOk();
    }

    public function test_index_includes_total_seconds_per_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        \App\Models\TimeLog::factory()->count(3)->create([
            'project_id' => $project->id,
            'duration_seconds' => 1200,
        ]);

        $this->actingAs($user)
            ->get('/projects')
            ->assertInertia(
                fn ($page) => $page
                    ->component('Project/Index')
                    ->where('projects.0.total_seconds', 3600)
            );
    }

    public function test_create_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/projects/create')
            ->assertOk();
    }

    public function test_store_creates_project(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->post('/projects', [
                'client_id' => $client->id,
                'title' => 'Website redesign',
                'status' => ProjectStatus::Active->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/projects');

        $this->assertDatabaseHas('projects', [
            'client_id' => $client->id,
            'title' => 'Website redesign',
            'status' => ProjectStatus::Active->value,
        ]);
    }

    public function test_store_requires_existing_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/projects', [
                'client_id' => 9999,
                'title' => 'X',
                'status' => ProjectStatus::Active->value,
            ])
            ->assertSessionHasErrors('client_id');
    }

    public function test_store_requires_title(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->post('/projects', [
                'client_id' => $client->id,
                'status' => ProjectStatus::Active->value,
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_store_rejects_invalid_status(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->post('/projects', [
                'client_id' => $client->id,
                'title' => 'X',
                'status' => 'invalid',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_show_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->get("/projects/{$project->id}")
            ->assertOk();
    }

    public function test_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->get("/projects/{$project->id}/edit")
            ->assertOk();
    }

    public function test_update_modifies_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'title' => 'Old',
            'status' => ProjectStatus::Active,
        ]);

        $this->actingAs($user)
            ->patch("/projects/{$project->id}", [
                'client_id' => $project->client_id,
                'title' => 'New',
                'status' => ProjectStatus::Completed->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect("/projects/{$project->id}");

        $project->refresh();
        $this->assertSame('New', $project->title);
        $this->assertSame(ProjectStatus::Completed, $project->status);
    }

    public function test_update_can_change_client(): void
    {
        $user = User::factory()->create();
        $oldClient = Client::factory()->create();
        $newClient = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $oldClient->id]);

        $this->actingAs($user)
            ->patch("/projects/{$project->id}", [
                'client_id' => $newClient->id,
                'title' => $project->title,
                'status' => $project->status->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($newClient->id, $project->fresh()->client_id);
    }

    public function test_destroy_deletes_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->delete("/projects/{$project->id}")
            ->assertRedirect('/projects');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_unauthenticated_user_cannot_access_projects(): void
    {
        $this->get('/projects')->assertRedirect('/login');
        $this->get('/projects/create')->assertRedirect('/login');
        $this->post('/projects', [])->assertRedirect('/login');
    }
}
