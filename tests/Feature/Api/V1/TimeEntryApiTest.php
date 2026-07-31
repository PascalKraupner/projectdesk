<?php

namespace Tests\Feature\Api\V1;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryApiTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): static
    {
        return $this->withToken(User::factory()->create()->createToken('test')->plainTextToken);
    }

    public function test_store_creates_a_manual_entry(): void
    {
        $project = Project::factory()->create();

        $this->withKey()->postJson('/api/v1/time-entries', [
            'project_id' => $project->id,
            'started_at' => '2026-07-03T09:00:00+00:00',
            'duration_seconds' => 5400,
            'note' => 'Hero section',
        ])
            ->assertCreated()
            ->assertJsonPath('data.project_id', $project->id)
            ->assertJsonPath('data.duration_seconds', 5400)
            ->assertJsonPath('data.note', 'Hero section')
            ->assertJsonPath('data.state', 'completed');

        $entry = TimeLog::first();
        $this->assertSame(5400, $entry->duration_seconds);
        $this->assertNotNull($entry->ended_at);
        $this->assertSame(
            $entry->started_at->addSeconds(5400)->toIso8601String(),
            $entry->ended_at->toIso8601String(),
        );
    }

    public function test_store_converts_an_offset_timestamp_to_utc(): void
    {
        $project = Project::factory()->create();

        // 09:00 at +02:00 is 07:00 UTC. The offset must be converted, not dropped.
        $this->withKey()->postJson('/api/v1/time-entries', [
            'project_id' => $project->id,
            'started_at' => '2026-07-30T09:00:00+02:00',
            'duration_seconds' => 3600,
        ])
            ->assertCreated()
            ->assertJsonPath('data.started_at', '2026-07-30T07:00:00+00:00')
            ->assertJsonPath('data.ended_at', '2026-07-30T08:00:00+00:00');
    }

    public function test_update_converts_an_offset_timestamp_to_utc(): void
    {
        $entry = TimeLog::factory()->create([
            'started_at' => '2026-07-03 09:00:00',
            'ended_at' => '2026-07-03 10:00:00',
            'duration_seconds' => 3600,
        ]);

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}", [
            'started_at' => '2026-07-30T09:00:00+02:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.started_at', '2026-07-30T07:00:00+00:00');
    }

    public function test_store_requires_a_known_project(): void
    {
        $this->withKey()->postJson('/api/v1/time-entries', [
            'project_id' => 999,
            'started_at' => '2026-07-03T09:00:00+00:00',
            'duration_seconds' => 60,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('project_id');
    }

    public function test_store_rejects_a_zero_duration(): void
    {
        $project = Project::factory()->create();

        $this->withKey()->postJson('/api/v1/time-entries', [
            'project_id' => $project->id,
            'started_at' => '2026-07-03T09:00:00+00:00',
            'duration_seconds' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('duration_seconds');
    }

    public function test_index_lists_entries_newest_first(): void
    {
        $project = Project::factory()->create();
        TimeLog::factory()->create(['project_id' => $project->id, 'started_at' => '2026-07-01 09:00:00']);
        TimeLog::factory()->create(['project_id' => $project->id, 'started_at' => '2026-07-05 09:00:00']);

        $this->withKey()->getJson('/api/v1/time-entries')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.started_at', '2026-07-05T09:00:00+00:00');
    }

    public function test_index_can_filter_by_project(): void
    {
        $wanted = Project::factory()->create();
        TimeLog::factory()->count(2)->create(['project_id' => $wanted->id]);
        TimeLog::factory()->count(3)->create();

        $this->withKey()->getJson("/api/v1/time-entries?project_id={$wanted->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_can_filter_by_client(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->count(2)->create(['project_id' => $project->id]);
        TimeLog::factory()->count(3)->create();

        $this->withKey()->getJson("/api/v1/time-entries?client_id={$client->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_can_filter_by_date_range(): void
    {
        $project = Project::factory()->create();
        TimeLog::factory()->create(['project_id' => $project->id, 'started_at' => '2026-06-15 09:00:00']);
        TimeLog::factory()->create(['project_id' => $project->id, 'started_at' => '2026-07-15 09:00:00']);
        TimeLog::factory()->create(['project_id' => $project->id, 'started_at' => '2026-08-15 09:00:00']);

        $this->withKey()
            ->getJson('/api/v1/time-entries?from=2026-07-01T00:00:00Z&to=2026-07-31T23:59:59Z')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_rejects_a_backwards_range(): void
    {
        $this->withKey()
            ->getJson('/api/v1/time-entries?from=2026-07-10&to=2026-07-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');
    }

    public function test_show_returns_an_entry(): void
    {
        $entry = TimeLog::factory()->create(['note' => 'Nav rework']);

        $this->withKey()->getJson("/api/v1/time-entries/{$entry->id}")
            ->assertOk()
            ->assertJsonPath('data.note', 'Nav rework');
    }

    public function test_update_patches_only_the_given_fields(): void
    {
        $entry = TimeLog::factory()->create([
            'started_at' => '2026-07-03 09:00:00',
            'ended_at' => '2026-07-03 10:00:00',
            'duration_seconds' => 3600,
            'note' => 'Original',
        ]);

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}", [
            'duration_seconds' => 7200,
        ])
            ->assertOk()
            ->assertJsonPath('data.duration_seconds', 7200)
            ->assertJsonPath('data.note', 'Original')
            ->assertJsonPath('data.started_at', '2026-07-03T09:00:00+00:00');
    }

    public function test_update_can_clear_a_note(): void
    {
        $entry = TimeLog::factory()->create([
            'started_at' => '2026-07-03 09:00:00',
            'ended_at' => '2026-07-03 10:00:00',
            'duration_seconds' => 3600,
            'note' => 'Original',
        ]);

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}", ['note' => null])
            ->assertOk()
            ->assertJsonPath('data.note', null);
    }

    public function test_update_refuses_a_running_entry(): void
    {
        $entry = TimeLog::factory()->running()->create();

        $this->withKey()->patchJson("/api/v1/time-entries/{$entry->id}", [
            'duration_seconds' => 60,
        ])->assertStatus(409);
    }

    public function test_destroy_deletes_an_entry(): void
    {
        $entry = TimeLog::factory()->create();

        $this->withKey()->deleteJson("/api/v1/time-entries/{$entry->id}")->assertNoContent();

        $this->assertDatabaseMissing('time_logs', ['id' => $entry->id]);
    }
}
