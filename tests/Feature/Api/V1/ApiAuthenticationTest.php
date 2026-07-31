<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_without_a_key_is_rejected(): void
    {
        $this->getJson('/api/v1/clients')->assertUnauthorized();
    }

    public function test_a_request_with_a_garbage_key_is_rejected(): void
    {
        $this->withToken('1|not-a-real-key')
            ->getJson('/api/v1/clients')
            ->assertUnauthorized();
    }

    public function test_a_valid_key_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/clients')
            ->assertOk();
    }

    public function test_an_expired_key_is_rejected(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('test', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($key)->getJson('/api/v1/clients')->assertUnauthorized();
    }

    public function test_a_revoked_key_is_rejected(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('test')->plainTextToken;
        $user->tokens()->delete();

        $this->withToken($key)->getJson('/api/v1/clients')->assertUnauthorized();
    }

    public function test_using_a_key_records_last_used_at(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('test')->plainTextToken;

        $this->assertNull($user->tokens()->first()->last_used_at);

        $this->withToken($key)->getJson('/api/v1/clients')->assertOk();

        $this->assertNotNull($user->tokens()->first()->last_used_at);
    }

    public function test_an_unauthenticated_api_request_is_not_redirected_to_login(): void
    {
        // No Accept header at all: the API must still answer with 401 JSON
        // rather than a redirect to the login page.
        $this->get('/api/v1/clients')->assertUnauthorized();
    }
}
