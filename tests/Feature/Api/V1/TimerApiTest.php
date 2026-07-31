<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimerApiTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): static
    {
        return $this->withToken(User::factory()->create()->createToken('test')->plainTextToken);
    }

    public function test_start_begins_a_timer(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Active]);

        $this->withKey()->postJson("/api/v1/projects/{$project->id}/timer/start", [
            'note' => 'Starting on auth',
        ])
            ->assertCreated()
            ->assertJsonPath('data.state', 'running')
            ->assertJsonPath('data.note', 'Starting on auth')
            ->assertJsonPath('data.duration_seconds', 0)
            ->assertJsonPath('data.ended_at', null);
    }

    public function test_start_refuses_when_a_timer_is_already_running(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Active]);
        TimeLog::factory()->running()->create(['project_id' => $project->id]);

        $this->withKey()->postJson("/api/v1/projects/{$project->id}/timer/start")
            ->assertStatus(409)
            ->assertJsonPath('message', 'A timer is already in progress.');
    }

    public function test_start_refuses_on_a_non_active_project(): void
    {
        $project = Project::factory()->create(['status' => ProjectStatus::Paused]);

        $this->withKey()->postJson("/api/v1/projects/{$project->id}/timer/start")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Cannot start a timer on a non-active project.');
    }

    public function test_pause_freezes_a_running_timer(): void
    {
        $entry = TimeLog::factory()->running()->create();

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}/pause")
            ->assertOk()
            ->assertJsonPath('data.state', 'paused')
            ->assertJsonPath('data.last_resumed_at', null);
    }

    public function test_pause_refuses_an_already_paused_timer(): void
    {
        $entry = TimeLog::factory()->paused()->create();

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}/pause")
            ->assertStatus(409);
    }

    public function test_resume_restarts_a_paused_timer(): void
    {
        $entry = TimeLog::factory()->paused()->create();

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}/resume")
            ->assertOk()
            ->assertJsonPath('data.state', 'running');
    }

    public function test_resume_refuses_when_another_timer_is_in_progress(): void
    {
        $paused = TimeLog::factory()->paused()->create();
        TimeLog::factory()->running()->create();

        $this->withKey()->patchJson("/api/v1/time-entries/{$paused->id}/resume")
            ->assertStatus(409);
    }

    public function test_stop_completes_a_timer_and_accumulates_time(): void
    {
        $entry = TimeLog::factory()->paused(600)->create();

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}/resume")->assertOk();

        $this->travel(30)->seconds();

        $response = $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}/stop")
            ->assertOk()
            ->assertJsonPath('data.state', 'completed');

        $this->assertSame(630, $response->json('data.duration_seconds'));
    }

    public function test_stop_refuses_an_already_stopped_timer(): void
    {
        $entry = TimeLog::factory()->create();

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}/stop")
            ->assertStatus(409);
    }

    public function test_current_returns_the_running_timer(): void
    {
        $entry = TimeLog::factory()->running()->create();

        $this->withKey()->getJson('/api/v1/timer')
            ->assertOk()
            ->assertJsonPath('data.id', $entry->id)
            ->assertJsonPath('data.state', 'running');
    }

    public function test_current_returns_null_when_nothing_is_tracking(): void
    {
        TimeLog::factory()->create();

        $this->withKey()->getJson('/api/v1/timer')
            ->assertOk()
            ->assertJsonPath('data', null);
    }
}
