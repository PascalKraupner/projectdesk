<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): static
    {
        return $this->withToken(User::factory()->create()->createToken('test')->plainTextToken);
    }

    public function test_index_lists_clients_with_totals(): void
    {
        $client = Client::factory()->create(['name' => 'Acme GmbH']);
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->create(['project_id' => $project->id, 'duration_seconds' => 3600]);

        $this->withKey()->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme GmbH')
            ->assertJsonPath('data.0.projects_count', 1)
            ->assertJsonPath('data.0.total_seconds', 3600)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_index_paginates(): void
    {
        Client::factory()->count(5)->create();

        $this->withKey()->getJson('/api/v1/clients?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5);
    }

    public function test_index_rejects_an_absurd_page_size(): void
    {
        $this->withKey()->getJson('/api/v1/clients?per_page=5000')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_store_creates_a_client(): void
    {
        $response = $this->withKey()->postJson('/api/v1/clients', [
            'name' => 'Acme GmbH',
            'email' => 'billing@acme.test',
            'hourly_rate' => 95.5,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme GmbH')
            ->assertJsonPath('data.currency', 'EUR');

        $this->assertEquals(95.5, $response->json('data.hourly_rate'));
        $this->assertDatabaseHas('clients', ['name' => 'Acme GmbH', 'currency' => 'EUR']);
    }

    public function test_store_requires_a_name(): void
    {
        $this->withKey()->postJson('/api/v1/clients', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_store_rejects_a_duplicate_email(): void
    {
        Client::factory()->create(['email' => 'billing@acme.test']);

        $this->withKey()->postJson('/api/v1/clients', [
            'name' => 'Other',
            'email' => 'billing@acme.test',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_store_rejects_an_unknown_currency(): void
    {
        $this->withKey()->postJson('/api/v1/clients', [
            'name' => 'Acme',
            'currency' => 'GBP',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('currency');
    }

    public function test_show_returns_a_client(): void
    {
        $client = Client::factory()->create(['name' => 'Acme GmbH']);

        $this->withKey()->getJson("/api/v1/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.name', 'Acme GmbH');
    }

    public function test_show_404s_for_an_unknown_client(): void
    {
        $this->withKey()->getJson('/api/v1/clients/999')->assertNotFound();
    }

    public function test_update_patches_only_the_given_fields(): void
    {
        $client = Client::factory()->create(['name' => 'Old', 'hourly_rate' => 50]);

        $response = $this->withKey()->patchJson("/api/v1/clients/{$client->id}", ['name' => 'New'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New');

        $this->assertEquals(50.0, $response->json('data.hourly_rate'));
    }

    public function test_update_lets_a_client_keep_its_own_email(): void
    {
        $client = Client::factory()->create(['email' => 'billing@acme.test']);

        $this->withKey()->patchJson("/api/v1/clients/{$client->id}", [
            'email' => 'billing@acme.test',
            'name' => 'Acme',
        ])->assertOk();
    }

    public function test_destroy_deletes_a_client(): void
    {
        $client = Client::factory()->create();

        $this->withKey()->deleteJson("/api/v1/clients/{$client->id}")->assertNoContent();

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }
}
