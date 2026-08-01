<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
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
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->count(2)->create([
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

        $response = $this->actingAs($user)
            ->post('/clients', [
                'name' => 'Acme Inc',
                'email' => 'hello@acme.test',
            ])
            ->assertSessionHasNoErrors();

        $client = Client::firstWhere('name', 'Acme Inc');
        $response->assertRedirect("/clients/{$client->id}");

        $this->assertDatabaseHas('clients', [
            'name' => 'Acme Inc',
            'email' => 'hello@acme.test',
            'currency' => 'EUR',
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
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'No Email Co',
            'email' => null,
        ]);
    }

    public function test_store_accepts_hourly_rate_and_currency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/clients', [
                'name' => 'Hourly Co',
                'email' => 'rate@example.test',
                'hourly_rate' => 95.5,
                'currency' => 'USD',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'Hourly Co',
            'hourly_rate' => 95.5,
            'currency' => 'USD',
        ]);
    }

    public function test_store_rejects_unsupported_currency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/clients', [
                'name' => 'Bad Currency',
                'currency' => 'GBP',
            ])
            ->assertSessionHasErrors('currency');
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
            ->assertRedirect("/clients/{$client->id}");

        $this->assertSame('New', $client->fresh()->name);
        $this->assertSame('new@x.test', $client->fresh()->email);
    }

    public function test_show_renders_client_with_projects(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'duration_seconds' => 1800,
            'started_at' => now()->subHour(),
            'ended_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($user)
            ->get("/clients/{$client->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Client/Show')
                ->where('client.id', $client->id)
                ->where('client.total_seconds', 1800)
                ->has('client.projects', 1)
            );
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

    public function test_the_billing_address_can_be_saved_and_edited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', [
            'name' => 'MÜNCH GmbH',
            'contact_person' => 'Markus Petershofen',
            'street' => 'Würzburger Straße 7',
            'postal_code' => '97753',
            'city' => 'Karlstadt',
            'vat_id' => 'DE811933977',
            'hourly_rate' => 80,
            'currency' => 'EUR',
        ])->assertSessionHasNoErrors();

        $client = Client::firstOrFail();
        $this->assertSame('Würzburger Straße 7', $client->street);
        $this->assertSame('97753', $client->postal_code);
        $this->assertSame('DE811933977', $client->vat_id);

        $this->actingAs($user)->patch("/clients/{$client->id}", [
            'name' => 'MÜNCH GmbH',
            'city' => 'Würzburg',
            'currency' => 'EUR',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Würzburg', $client->fresh()->city);
    }

    public function test_the_edit_form_receives_the_billing_address(): void
    {
        $client = Client::factory()->billable()->create(['city' => 'Karlstadt']);

        $this->actingAs(User::factory()->create())
            ->get("/clients/{$client->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('client.city', 'Karlstadt'));
    }
}
