<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use App\Services\TimesheetService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Europe/Berlin';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.display_timezone' => self::TZ]);
    }

    private function service(): TimesheetService
    {
        return app(TimesheetService::class);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function july(): array
    {
        return [
            CarbonImmutable::parse('2026-07-01', self::TZ)->startOfDay(),
            CarbonImmutable::parse('2026-07-31', self::TZ)->endOfDay(),
        ];
    }

    public function test_client_document_groups_logs_by_project_oldest_first(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create();
        $alpha = Project::factory()->create(['client_id' => $client->id, 'title' => 'Alpha']);
        $beta = Project::factory()->create(['client_id' => $client->id, 'title' => 'Beta']);

        TimeLog::factory()->create([
            'project_id' => $alpha->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-10 10:00:00',
            'duration_seconds' => 3600,
            'note' => 'Later',
        ]);
        TimeLog::factory()->create([
            'project_id' => $alpha->id,
            'started_at' => '2026-07-02 09:00:00',
            'ended_at' => '2026-07-02 09:30:00',
            'duration_seconds' => 1800,
            'note' => 'Earlier',
        ]);
        TimeLog::factory()->create([
            'project_id' => $beta->id,
            'started_at' => '2026-07-05 09:00:00',
            'ended_at' => '2026-07-05 11:00:00',
            'duration_seconds' => 7200,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertSame(['Alpha', 'Beta'], array_column($doc['groups'], 'title'));
        $this->assertSame(['Earlier', 'Later'], array_column($doc['groups'][0]['logs'], 'note'));
        $this->assertSame('00:30:00', $doc['groups'][0]['logs'][0]['duration']);
        $this->assertSame(12600, $doc['total_seconds']);
        $this->assertSame('03:30:00', $doc['total_duration']);
        $this->assertSame('3.50', $doc['total_hours']);
    }

    public function test_client_document_excludes_logs_outside_the_range(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-06-30 09:00:00',
            'ended_at' => '2026-06-30 10:00:00',
            'duration_seconds' => 3600,
        ]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-08-01 09:00:00',
            'ended_at' => '2026-08-01 10:00:00',
            'duration_seconds' => 3600,
        ]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-31 12:00:00',
            'ended_at' => '2026-07-31 13:00:00',
            'duration_seconds' => 3600,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertCount(1, $doc['groups']);
        $this->assertCount(1, $doc['groups'][0]['logs']);
        $this->assertSame(3600, $doc['total_seconds']);
    }

    public function test_log_times_are_rendered_in_the_display_timezone(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-15 07:15:00',
            'ended_at' => '2026-07-15 08:15:00',
            'duration_seconds' => 3600,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertSame('09:15', $doc['groups'][0]['logs'][0]['started']);
        $this->assertSame('2026-07-15', $doc['groups'][0]['logs'][0]['date']);
    }

    public function test_a_late_evening_entry_is_dated_in_the_display_timezone(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        // 2026-07-15 22:30 UTC is 2026-07-16 00:30 in Berlin.
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-15 22:30:00',
            'ended_at' => '2026-07-15 23:30:00',
            'duration_seconds' => 3600,
        ]);

        $doc = $this->service()->clientDocument(
            $client,
            CarbonImmutable::parse('2026-07-16', self::TZ)->startOfDay(),
            CarbonImmutable::parse('2026-07-16', self::TZ)->endOfDay(),
        );

        $this->assertCount(1, $doc['groups']);
        $this->assertSame('2026-07-16', $doc['groups'][0]['logs'][0]['date']);
        $this->assertSame('00:30', $doc['groups'][0]['logs'][0]['started']);
    }

    public function test_range_boundaries_follow_the_display_timezone(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        // 2026-07-31 22:00 UTC is already 2026-08-01 in Berlin, so July excludes it.
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-31 22:00:00',
            'ended_at' => '2026-07-31 23:00:00',
            'duration_seconds' => 3600,
        ]);
        // 2026-06-30 22:30 UTC is already 2026-07-01 in Berlin, so July includes it.
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-06-30 22:30:00',
            'ended_at' => '2026-06-30 23:30:00',
            'duration_seconds' => 3600,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertCount(1, $doc['groups'][0]['logs']);
        $this->assertSame('2026-07-01', $doc['groups'][0]['logs'][0]['date']);
    }

    public function test_client_document_excludes_running_and_paused_logs(): void
    {
        [$from, $to] = $this->july();
        $this->travelTo('2026-07-15 12:00:00');

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->running()->create(['project_id' => $project->id]);
        TimeLog::factory()->paused()->create(['project_id' => $project->id]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertSame([], $doc['groups']);
        $this->assertSame(0, $doc['total_seconds']);
        $this->assertSame('00:00:00', $doc['total_duration']);
    }

    public function test_client_document_drops_projects_without_logs_in_the_range(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create();
        $tracked = Project::factory()->create(['client_id' => $client->id, 'title' => 'Tracked']);
        Project::factory()->create(['client_id' => $client->id, 'title' => 'Untouched']);

        TimeLog::factory()->create([
            'project_id' => $tracked->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-10 10:00:00',
            'duration_seconds' => 3600,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertSame(['Tracked'], array_column($doc['groups'], 'title'));
    }

    public function test_document_carries_money_when_the_client_has_an_hourly_rate(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create(['hourly_rate' => 100, 'currency' => 'EUR']);
        $project = Project::factory()->create(['client_id' => $client->id]);

        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-10 10:30:00',
            'duration_seconds' => 5400,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertStringContainsString('100', $doc['rate']);
        $this->assertStringContainsString('150', $doc['total_amount']);
        $this->assertStringContainsString('150', $doc['groups'][0]['amount']);
    }

    public function test_document_omits_money_when_the_client_has_no_hourly_rate(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create(['hourly_rate' => null]);
        $project = Project::factory()->create(['client_id' => $client->id]);

        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-10 10:00:00',
            'duration_seconds' => 3600,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertNull($doc['rate']);
        $this->assertNull($doc['total_amount']);
        $this->assertNull($doc['groups'][0]['amount']);
    }

    public function test_project_document_is_a_single_untitled_group(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create(['name' => 'Acme GmbH']);
        $project = Project::factory()->create(['client_id' => $client->id, 'title' => 'Website']);

        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-10 10:00:00',
            'duration_seconds' => 3600,
        ]);

        $doc = $this->service()->projectDocument($project, $from, $to);

        $this->assertSame('Website', $doc['title']);
        $this->assertSame('Acme GmbH', $doc['subtitle']);
        $this->assertCount(1, $doc['groups']);
        $this->assertNull($doc['groups'][0]['title']);
        $this->assertSame(3600, $doc['total_seconds']);
    }

    public function test_durations_past_twenty_four_hours_do_not_wrap(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-12 09:00:00',
            'duration_seconds' => 172800,
        ]);

        $doc = $this->service()->clientDocument($client, $from, $to);

        $this->assertSame('48:00:00', $doc['total_duration']);
        $this->assertSame('48.00', $doc['total_hours']);
    }

    public function test_period_and_filename_reflect_the_range(): void
    {
        $client = Client::factory()->create(['name' => 'Acme GmbH']);

        $doc = $this->service()->clientDocument(
            $client,
            CarbonImmutable::parse('2026-05-04')->startOfDay(),
            CarbonImmutable::parse('2026-05-17')->endOfDay(),
        );

        $this->assertSame('2026-05-04 – 2026-05-17', $doc['period']);
        $this->assertSame('timesheet-acme-gmbh-2026-05-04-2026-05-17.pdf', $doc['filename']);
    }
}
