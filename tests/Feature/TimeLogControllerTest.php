<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_starts_a_new_log(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->from("/projects/{$project->id}")
            ->post("/projects/{$project->id}/time-logs")
            ->assertSessionHasNoErrors()
            ->assertRedirect("/projects/{$project->id}");

        $this->assertDatabaseCount('time_logs', 1);
        $this->assertNull(TimeLog::first()->ended_at);
        $this->assertSame($project->id, TimeLog::first()->project_id);
    }

    public function test_store_can_attach_a_note_at_start(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/time-logs", ['note' => 'Starting work on auth'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Starting work on auth', TimeLog::first()->note);
    }

    public function test_store_blocks_starting_when_another_log_is_running(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        TimeLog::factory()->running()->create(['project_id' => $project->id]);

        $this->expectException(\RuntimeException::class);

        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->post("/projects/{$project->id}/time-logs");
    }

    public function test_update_stops_the_log_and_records_duration(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $log = TimeLog::factory()->running()->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($user)
            ->patch("/time-logs/{$log->id}")
            ->assertSessionHasNoErrors();

        $log->refresh();
        $this->assertNotNull($log->ended_at);
        $this->assertGreaterThanOrEqual(1790, $log->duration_seconds);
        $this->assertLessThanOrEqual(1810, $log->duration_seconds);
    }

    public function test_update_preserves_existing_note(): void
    {
        $user = User::factory()->create();
        $log = TimeLog::factory()->running()->create([
            'started_at' => now()->subMinutes(5),
            'note' => 'Already typed while running',
        ]);

        $this->actingAs($user)
            ->patch("/time-logs/{$log->id}")
            ->assertSessionHasNoErrors();

        $this->assertSame('Already typed while running', $log->fresh()->note);
    }

    public function test_update_blocks_stopping_an_already_stopped_log(): void
    {
        $user = User::factory()->create();
        $log = TimeLog::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->patch("/time-logs/{$log->id}");
    }

    public function test_update_note_sets_the_note_on_a_stopped_log(): void
    {
        $user = User::factory()->create();
        $log = TimeLog::factory()->create(['note' => null]);

        $this->actingAs($user)
            ->patch("/time-logs/{$log->id}/note", ['note' => 'Refactored auth'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Refactored auth', $log->fresh()->note);
    }

    public function test_update_note_allows_clearing_the_note(): void
    {
        $user = User::factory()->create();
        $log = TimeLog::factory()->create(['note' => 'Old note']);

        $this->actingAs($user)
            ->patch("/time-logs/{$log->id}/note", ['note' => null])
            ->assertSessionHasNoErrors();

        $this->assertNull($log->fresh()->note);
    }

    public function test_store_manual_creates_a_completed_log_with_duration(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/time-logs/manual", [
                'started_at' => '2026-04-19 09:00:00',
                'ended_at' => '2026-04-19 10:30:00',
                'note' => 'Worked on invoices',
            ])
            ->assertSessionHasNoErrors();

        $log = TimeLog::first();
        $this->assertSame($project->id, $log->project_id);
        $this->assertNotNull($log->ended_at);
        $this->assertSame(5400, $log->duration_seconds);
        $this->assertSame('Worked on invoices', $log->note);
    }

    public function test_store_manual_does_not_block_when_a_timer_is_running(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        TimeLog::factory()->running()->create(['project_id' => $project->id]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/time-logs/manual", [
                'started_at' => '2026-04-19 09:00:00',
                'ended_at' => '2026-04-19 09:30:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('time_logs', 2);
    }

    public function test_store_manual_validates_end_after_start(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/time-logs/manual", [
                'started_at' => '2026-04-19 10:00:00',
                'ended_at' => '2026-04-19 09:00:00',
            ])
            ->assertSessionHasErrors('ended_at');

        $this->assertDatabaseCount('time_logs', 0);
    }

    public function test_update_manual_updates_times_and_recomputes_duration(): void
    {
        $user = User::factory()->create();
        $log = TimeLog::factory()->create([
            'started_at' => '2026-04-19 09:00:00',
            'ended_at' => '2026-04-19 09:30:00',
            'duration_seconds' => 1800,
            'note' => 'Old',
        ]);

        $this->actingAs($user)
            ->patch("/time-logs/{$log->id}/manual", [
                'started_at' => '2026-04-19 14:00:00',
                'ended_at' => '2026-04-19 15:15:00',
                'note' => 'New',
            ])
            ->assertSessionHasNoErrors();

        $log->refresh();
        $this->assertSame(4500, $log->duration_seconds);
        $this->assertSame('New', $log->note);
    }

    public function test_update_manual_blocks_editing_a_running_log(): void
    {
        $user = User::factory()->create();
        $log = TimeLog::factory()->running()->create();

        $this->expectException(\RuntimeException::class);

        $this->withoutExceptionHandling()
            ->actingAs($user)
            ->patch("/time-logs/{$log->id}/manual", [
                'started_at' => '2026-04-19 09:00:00',
                'ended_at' => '2026-04-19 10:00:00',
            ]);
    }

    public function test_destroy_deletes_the_log(): void
    {
        $user = User::factory()->create();
        $log = TimeLog::factory()->create();

        $this->actingAs($user)
            ->delete("/time-logs/{$log->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('time_logs', ['id' => $log->id]);
    }

    public function test_project_show_includes_logs_and_total(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        TimeLog::factory()->count(3)->create([
            'project_id' => $project->id,
            'duration_seconds' => 600,
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}");

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Project/Show')
                ->where('project.total_seconds', 1800)
                ->has('project.time_logs', 3)
        );
    }

    public function test_unauthenticated_user_cannot_use_time_log_routes(): void
    {
        $project = Project::factory()->create();
        $log = TimeLog::factory()->create();

        $this->post("/projects/{$project->id}/time-logs")->assertRedirect('/login');
        $this->post("/projects/{$project->id}/time-logs/manual")->assertRedirect('/login');
        $this->patch("/time-logs/{$log->id}")->assertRedirect('/login');
        $this->patch("/time-logs/{$log->id}/manual")->assertRedirect('/login');
        $this->delete("/time-logs/{$log->id}")->assertRedirect('/login');
    }
}
