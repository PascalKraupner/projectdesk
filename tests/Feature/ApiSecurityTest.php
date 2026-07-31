<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_there_is_no_self_service_registration(): void
    {
        $names = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->all();

        $this->assertNotContains('register', $names);
        $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.test',
            'password' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.test']);
    }

    public function test_the_openapi_spec_requires_a_session(): void
    {
        $this->get('/openapi.yaml')->assertRedirect('/login');
    }

    public function test_the_openapi_spec_is_not_reachable_with_an_api_key(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->get('/openapi.yaml')
            ->assertRedirect('/login');
    }

    public function test_the_openapi_spec_is_served_to_a_logged_in_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/openapi.yaml');

        $response->assertOk()->assertHeader('content-type', 'application/yaml');
        $this->assertStringContainsString('openapi: 3.1.0', $response->streamedContent());
    }

    public function test_an_api_key_cannot_reach_session_protected_routes(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('test')->plainTextToken;

        foreach (['/api-keys', '/', '/clients', '/profile'] as $path) {
            $this->withToken($key)->get($path)->assertRedirect('/login');
        }
    }

    public function test_api_responses_carry_rate_limit_headers(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/clients')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', 120);
    }

    public function test_the_api_throttles_once_the_limit_is_exceeded(): void
    {
        RateLimiter::for('api', fn () => Limit::perMinute(2));

        $user = User::factory()->create();
        $key = $user->createToken('test')->plainTextToken;

        $this->withToken($key)->getJson('/api/v1/clients')->assertOk();
        $this->withToken($key)->getJson('/api/v1/clients')->assertOk();
        $this->withToken($key)->getJson('/api/v1/clients')->assertStatus(429);
    }

    public function test_unauthenticated_api_calls_are_also_throttled(): void
    {
        RateLimiter::for('api', fn () => Limit::perMinute(2));

        $this->getJson('/api/v1/clients')->assertUnauthorized();
        $this->getJson('/api/v1/clients')->assertUnauthorized();
        $this->getJson('/api/v1/clients')->assertStatus(429);
    }
}
