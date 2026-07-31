<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_a_logged_in_user(): void
    {
        $this->get('/api-keys')->assertRedirect('/login');
    }

    public function test_store_requires_a_logged_in_user(): void
    {
        $this->post('/api-keys', ['name' => 'Sneaky'])->assertRedirect('/login');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_key_cannot_be_created_using_a_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('existing')->plainTextToken;

        $this->withToken($key)
            ->postJson('/api-keys', ['name' => 'Bootstrapped'])
            ->assertUnauthorized();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_index_lists_the_users_keys(): void
    {
        $user = User::factory()->create();
        $user->createToken('Laptop CLI');

        $this->actingAs($user)->get('/api-keys')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/ApiKeys')
                ->has('keys', 1)
                ->where('keys.0.name', 'Laptop CLI'));
    }

    public function test_store_creates_a_key_and_flashes_the_plaintext_once(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/api-keys')
            ->post('/api-keys', ['name' => 'Laptop CLI']);

        $response->assertRedirect('/api-keys')->assertSessionHas('apiKey');

        $plainTextKey = session('apiKey');
        $this->assertNotEmpty($plainTextKey);
        $this->assertStringContainsString('|', $plainTextKey);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Laptop CLI']);

        // The redirect target shows it once...
        $this->actingAs($user)->get('/api-keys')
            ->assertInertia(fn ($page) => $page->where('flash.apiKey', $plainTextKey));

        // ...and the request after that no longer carries it.
        $this->actingAs($user)->get('/api-keys')
            ->assertInertia(fn ($page) => $page->where('flash.apiKey', null));
    }

    public function test_only_the_hash_of_a_key_is_stored(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api-keys', ['name' => 'Laptop CLI']);

        [$id, $secret] = explode('|', session('apiKey'), 2);

        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $secret]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $id,
            'token' => hash('sha256', $secret),
        ]);
    }

    public function test_a_created_key_authenticates_against_the_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api-keys', ['name' => 'Laptop CLI']);

        $this->withToken(session('apiKey'))->getJson('/api/v1/clients')->assertOk();
    }

    public function test_store_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/api-keys')
            ->post('/api-keys', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_store_rejects_an_expiry_in_the_past(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/api-keys')
            ->post('/api-keys', ['name' => 'Stale', 'expires_at' => '2020-01-01'])
            ->assertSessionHasErrors('expires_at');
    }

    public function test_store_can_set_an_expiry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api-keys', [
            'name' => 'Temporary',
            'expires_at' => now()->addDays(7)->toDateString(),
        ]);

        $this->assertNotNull($user->tokens()->first()->expires_at);
    }

    public function test_an_expiry_lasts_until_the_end_of_the_chosen_day(): void
    {
        config(['app.display_timezone' => 'Europe/Berlin']);
        $this->travelTo('2026-08-01 10:00:00');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/api-keys', [
            'name' => 'Temporary',
            'expires_at' => '2026-09-01',
        ]);

        // End of 1 September in Berlin (UTC+2 in summer) is 21:59:59 UTC.
        $this->assertSame(
            '2026-09-01T21:59:59+00:00',
            $user->tokens()->first()->expires_at->toIso8601String(),
        );
    }

    /**
     * One request per test on purpose: the Sanctum guard caches its resolved user
     * on the application instance, which persists across requests within a single
     * test and would mask the expiry.
     */
    public function test_a_key_still_works_on_its_expiry_day(): void
    {
        $key = $this->keyExpiring('2026-09-01');

        $this->travelTo('2026-09-01 12:00:00');

        $this->withToken($key)->getJson('/api/v1/clients')->assertOk();
    }

    public function test_a_key_stops_working_the_day_after_it_expires(): void
    {
        $key = $this->keyExpiring('2026-09-01');

        $this->travelTo('2026-09-02 12:00:00');

        $this->withToken($key)->getJson('/api/v1/clients')->assertUnauthorized();
    }

    private function keyExpiring(string $date): string
    {
        config(['app.display_timezone' => 'Europe/Berlin']);
        $this->travelTo('2026-08-01 10:00:00');

        $this->actingAs(User::factory()->create())
            ->post('/api-keys', ['name' => 'Temporary', 'expires_at' => $date]);

        $key = session('apiKey');

        // Sanctum's guard consults the web guard before looking at the token, so the
        // session left behind by actingAs would authenticate the later request on its
        // own and the expiry would never be reached.
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return $key;
    }

    public function test_destroy_revokes_a_key(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Laptop CLI')->accessToken;

        $this->actingAs($user)
            ->from('/api-keys')
            ->delete("/api-keys/{$token->id}")
            ->assertRedirect('/api-keys');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_destroy_leaves_another_users_key_alone(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherToken = $other->createToken('Not yours')->accessToken;

        $this->actingAs($user)->delete("/api-keys/{$otherToken->id}");

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
    }
}
