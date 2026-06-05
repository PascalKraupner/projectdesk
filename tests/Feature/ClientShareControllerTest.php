<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientShareControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_enable_sharing_and_get_signed_url_back(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $expires = CarbonImmutable::now()->addDays(7);

        $this->actingAs($user)
            ->post("/clients/{$client->id}/share", [
                'expires_at' => $expires->toIso8601String(),
            ])
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertNotNull($client->share_token);
        $this->assertNotNull($client->share_expires_at);

        $this->actingAs($user)
            ->get("/clients/{$client->id}")
            ->assertInertia(fn ($page) => $page
                ->where('share_url', fn ($url) => is_string($url) && str_contains($url, "token={$client->share_token}"))
                ->has('share_expires_at')
            );
    }

    public function test_show_renders_inertia_page_for_a_valid_signed_url(): void
    {
        $client = Client::factory()->create(['name' => 'Acme Inc', 'hourly_rate' => 99.5, 'currency' => 'EUR']);
        $project = Project::factory()->create(['client_id' => $client->id, 'title' => 'Acme dashboard']);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'duration_seconds' => 1800,
            'started_at' => now()->subHour(),
            'ended_at' => now()->subMinutes(30),
        ]);

        $client->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $url = URL::temporarySignedRoute('clients.share', $client->share_expires_at, [
            'client' => $client->id,
            'token' => $client->share_token,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Client/PublicShare')
                ->where('client.name', 'Acme Inc')
                ->where('client.total_seconds', 1800)
                ->where('client.currency', 'EUR')
                ->where('client.hourly_rate', 99.5)
                ->where('client.projects.0.title', 'Acme dashboard')
                ->where('client.projects.0.total_seconds', 1800)
                ->where('client.projects.0.time_logs.0.duration_seconds', 1800)
            );
    }

    public function test_show_403s_when_signature_is_tampered(): void
    {
        $client = Client::factory()->create();
        $client->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $url = URL::temporarySignedRoute('clients.share', $client->share_expires_at, [
            'client' => $client->id,
            'token' => $client->share_token,
        ]);

        $tampered = $url.'X';

        $this->get($tampered)
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page->component('Client/PublicShareExpired'));
    }

    public function test_show_403s_when_url_has_expired(): void
    {
        $client = Client::factory()->create();
        $expires = CarbonImmutable::create(2026, 4, 19, 12, 0, 0);
        $client->update(['share_token' => 'tok-abc', 'share_expires_at' => $expires]);

        CarbonImmutable::setTestNow($expires->subMinute());
        $url = URL::temporarySignedRoute('clients.share', $expires, [
            'client' => $client->id,
            'token' => $client->share_token,
        ]);

        CarbonImmutable::setTestNow($expires->addMinute());
        $this->get($url)
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page->component('Client/PublicShareExpired'));
        CarbonImmutable::setTestNow();
    }

    public function test_show_403s_when_token_was_rotated(): void
    {
        $client = Client::factory()->create();
        $client->update([
            'share_token' => 'tok-old',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $oldUrl = URL::temporarySignedRoute('clients.share', $client->share_expires_at, [
            'client' => $client->id,
            'token' => 'tok-old',
        ]);

        $client->update(['share_token' => 'tok-new']);

        $this->get($oldUrl)
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page->component('Client/PublicShareExpired'));
    }

    public function test_show_403s_when_sharing_was_revoked(): void
    {
        $client = Client::factory()->create();
        $client->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $url = URL::temporarySignedRoute('clients.share', $client->share_expires_at, [
            'client' => $client->id,
            'token' => $client->share_token,
        ]);

        $client->update(['share_token' => null, 'share_expires_at' => null]);

        $this->get($url)
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page->component('Client/PublicShareExpired'));
    }

    public function test_owner_can_regenerate_to_invalidate_old_links(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $client->update([
            'share_token' => 'tok-old',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $this->actingAs($user)
            ->post("/clients/{$client->id}/share", [
                'expires_at' => CarbonImmutable::now()->addDays(14)->toIso8601String(),
                'regenerate' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotSame('tok-old', $client->fresh()->share_token);
    }

    public function test_owner_can_revoke_sharing(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $client->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $this->actingAs($user)
            ->delete("/clients/{$client->id}/share")
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertNull($client->share_token);
        $this->assertNull($client->share_expires_at);
    }

    public function test_unauthenticated_user_cannot_hit_owner_endpoints(): void
    {
        $client = Client::factory()->create();

        $this->post("/clients/{$client->id}/share", [
            'expires_at' => CarbonImmutable::now()->addDay()->toIso8601String(),
        ])->assertRedirect('/login');

        $this->delete("/clients/{$client->id}/share")->assertRedirect('/login');
    }
}
