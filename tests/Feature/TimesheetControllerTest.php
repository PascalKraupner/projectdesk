<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_timesheet_downloads_a_pdf_for_the_current_month_by_default(): void
    {
        $this->travelTo('2026-07-15 12:00:00');

        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Acme GmbH']);
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-03 09:00:00',
            'ended_at' => '2026-07-03 11:00:00',
            'duration_seconds' => 7200,
        ]);

        $response = $this->actingAs($user)->get("/clients/{$client->id}/timesheet.pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'timesheet-acme-gmbh-2026-07-01-2026-07-31.pdf',
            $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_project_timesheet_downloads_a_pdf(): void
    {
        $this->travelTo('2026-07-15 12:00:00');

        $user = User::factory()->create();
        $project = Project::factory()->create(['title' => 'Website Relaunch']);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-03 09:00:00',
            'ended_at' => '2026-07-03 11:00:00',
            'duration_seconds' => 7200,
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/timesheet.pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'timesheet-website-relaunch-2026-07-01-2026-07-31.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_timesheet_accepts_an_explicit_range(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Acme GmbH']);

        $response = $this->actingAs($user)
            ->get("/clients/{$client->id}/timesheet.pdf?from=2026-05-01&to=2026-05-31");

        $response->assertOk();
        $this->assertStringContainsString(
            'timesheet-acme-gmbh-2026-05-01-2026-05-31.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_timesheet_defaults_from_to_the_start_of_the_to_month(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Acme GmbH']);

        $response = $this->actingAs($user)
            ->get("/clients/{$client->id}/timesheet.pdf?to=2026-04-20");

        $response->assertOk();
        $this->assertStringContainsString(
            'timesheet-acme-gmbh-2026-04-01-2026-04-20.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_timesheet_rejects_a_range_that_ends_before_it_starts(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->get("/clients/{$client->id}/timesheet.pdf?from=2026-07-10&to=2026-07-01")
            ->assertSessionHasErrors('to');
    }

    public function test_timesheet_rejects_an_unparseable_date(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->get("/clients/{$client->id}/timesheet.pdf?from=not-a-date")
            ->assertSessionHasErrors('from');
    }

    public function test_client_timesheet_requires_auth(): void
    {
        $client = Client::factory()->create();

        $this->get("/clients/{$client->id}/timesheet.pdf")->assertRedirect('/login');
    }

    public function test_project_timesheet_requires_auth(): void
    {
        $project = Project::factory()->create();

        $this->get("/projects/{$project->id}/timesheet.pdf")->assertRedirect('/login');
    }
}
