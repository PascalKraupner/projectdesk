<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProjectShareControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_enable_sharing_and_get_signed_url_back(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $expires = CarbonImmutable::now()->addDays(7);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/share", [
                'expires_at' => $expires->toIso8601String(),
            ])
            ->assertSessionHasNoErrors();

        $project->refresh();
        $this->assertNotNull($project->share_token);
        $this->assertNotNull($project->share_expires_at);

        $this->actingAs($user)
            ->get("/projects/{$project->id}")
            ->assertInertia(fn ($page) => $page
                ->where('share_url', fn ($url) => is_string($url) && str_contains($url, "token={$project->share_token}"))
                ->has('share_expires_at')
            );
    }

    public function test_show_renders_inertia_page_for_a_valid_signed_url(): void
    {
        $project = Project::factory()->create(['title' => 'Acme dashboard']);
        TimeLog::factory()->create(['project_id' => $project->id, 'duration_seconds' => 1800]);

        $project->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $url = URL::temporarySignedRoute('projects.share', $project->share_expires_at, [
            'project' => $project->id,
            'token' => $project->share_token,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Project/PublicShare')
                ->where('project.title', 'Acme dashboard')
                ->where('project.total_seconds', 1800)
                ->where('project.status', $project->status->value)
            );
    }

    public function test_show_403s_when_signature_is_tampered(): void
    {
        $project = Project::factory()->create();
        $project->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $url = URL::temporarySignedRoute('projects.share', $project->share_expires_at, [
            'project' => $project->id,
            'token' => $project->share_token,
        ]);

        $tampered = $url.'X';

        $this->get($tampered)->assertForbidden();
    }

    public function test_show_403s_when_url_has_expired(): void
    {
        $project = Project::factory()->create();
        $expires = CarbonImmutable::create(2026, 4, 19, 12, 0, 0);
        $project->update(['share_token' => 'tok-abc', 'share_expires_at' => $expires]);

        CarbonImmutable::setTestNow($expires->subMinute());
        $url = URL::temporarySignedRoute('projects.share', $expires, [
            'project' => $project->id,
            'token' => $project->share_token,
        ]);

        CarbonImmutable::setTestNow($expires->addMinute());
        $this->get($url)->assertForbidden();
        CarbonImmutable::setTestNow();
    }

    public function test_show_403s_when_token_was_rotated(): void
    {
        $project = Project::factory()->create();
        $project->update([
            'share_token' => 'tok-old',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $oldUrl = URL::temporarySignedRoute('projects.share', $project->share_expires_at, [
            'project' => $project->id,
            'token' => 'tok-old',
        ]);

        $project->update(['share_token' => 'tok-new']);

        $this->get($oldUrl)->assertForbidden();
    }

    public function test_show_403s_when_sharing_was_revoked(): void
    {
        $project = Project::factory()->create();
        $project->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $url = URL::temporarySignedRoute('projects.share', $project->share_expires_at, [
            'project' => $project->id,
            'token' => $project->share_token,
        ]);

        $project->update(['share_token' => null, 'share_expires_at' => null]);

        $this->get($url)->assertForbidden();
    }

    public function test_owner_can_regenerate_to_invalidate_old_links(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->update([
            'share_token' => 'tok-old',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/share", [
                'expires_at' => CarbonImmutable::now()->addDays(14)->toIso8601String(),
                'regenerate' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotSame('tok-old', $project->fresh()->share_token);
    }

    public function test_owner_can_revoke_sharing(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->update([
            'share_token' => 'tok-abc',
            'share_expires_at' => CarbonImmutable::now()->addDay(),
        ]);

        $this->actingAs($user)
            ->delete("/projects/{$project->id}/share")
            ->assertSessionHasNoErrors();

        $project->refresh();
        $this->assertNull($project->share_token);
        $this->assertNull($project->share_expires_at);
    }

    public function test_unauthenticated_user_cannot_hit_owner_endpoints(): void
    {
        $project = Project::factory()->create();

        $this->post("/projects/{$project->id}/share", [
            'expires_at' => CarbonImmutable::now()->addDay()->toIso8601String(),
        ])->assertRedirect('/login');

        $this->delete("/projects/{$project->id}/share")->assertRedirect('/login');
    }
}
