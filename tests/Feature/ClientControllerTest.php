<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_clients(): void
    {
        $user = User::factory()->create();
        Client::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/clients')
            ->assertOk();
    }

    public function test_index_includes_total_seconds_per_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $project = \App\Models\Project::factory()->create(['client_id' => $client->id]);
        \App\Models\TimeLog::factory()->count(2)->create([
            'project_id' => $project->id,
            'duration_seconds' => 900,
        ]);

        $this->actingAs($user)
            ->get('/clients')
            ->assertInertia(
                fn ($page) => $page
                    ->component('Client/Index')
                    ->where('clients.0.total_seconds', 1800)
            );
    }

    public function test_create_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/clients/create')
            ->assertOk();
    }

    public function test_store_creates_client(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/clients', [
                'name' => 'Acme Inc',
                'email' => 'hello@acme.test',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/clients');

        $this->assertDatabaseHas('clients', [
            'name' => 'Acme Inc',
            'email' => 'hello@acme.test',
        ]);
    }

    public function test_store_allows_null_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/clients', [
                'name' => 'No Email Co',
                'email' => null,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/clients');

        $this->assertDatabaseHas('clients', [
            'name' => 'No Email Co',
            'email' => null,
        ]);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/clients', ['email' => 'a@b.test'])
            ->assertSessionHasErrors('name');
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $user = User::factory()->create();
        Client::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($user)
            ->post('/clients', [
                'name' => 'Dup',
                'email' => 'taken@example.test',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->get("/clients/{$client->id}/edit")
            ->assertOk();
    }

    public function test_update_modifies_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Old', 'email' => 'old@x.test']);

        $this->actingAs($user)
            ->patch("/clients/{$client->id}", [
                'name' => 'New',
                'email' => 'new@x.test',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/clients');

        $this->assertSame('New', $client->fresh()->name);
        $this->assertSame('new@x.test', $client->fresh()->email);
    }

    public function test_update_allows_keeping_same_email(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['email' => 'same@x.test']);

        $this->actingAs($user)
            ->patch("/clients/{$client->id}", [
                'name' => 'Renamed',
                'email' => 'same@x.test',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_update_rejects_email_taken_by_another_client(): void
    {
        $user = User::factory()->create();
        Client::factory()->create(['email' => 'taken@x.test']);
        $client = Client::factory()->create(['email' => 'mine@x.test']);

        $this->actingAs($user)
            ->patch("/clients/{$client->id}", [
                'name' => 'X',
                'email' => 'taken@x.test',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_destroy_deletes_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->delete("/clients/{$client->id}")
            ->assertRedirect('/clients');

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_unauthenticated_user_cannot_access_clients(): void
    {
        $this->get('/clients')->assertRedirect('/login');
        $this->get('/clients/create')->assertRedirect('/login');
        $this->post('/clients', [])->assertRedirect('/login');
    }
}
