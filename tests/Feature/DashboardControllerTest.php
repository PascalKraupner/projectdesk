<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_aggregate_stats(): void
    {
        $user = User::factory()->create();

        Client::factory()->count(2)->create();
        Project::factory()->create(['status' => ProjectStatus::Active]);
        Project::factory()->create(['status' => ProjectStatus::Paused]);

        $project = Project::factory()->create(['status' => ProjectStatus::Active]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => now()->startOfWeek()->addHour(),
            'duration_seconds' => 1800,
        ]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => now()->startOfMonth()->addDay(),
            'duration_seconds' => 3600,
        ]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => now()->subMonths(2),
            'duration_seconds' => 9999,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('totalClients', fn ($v) => $v >= 2)
                ->where('totalProjects', fn ($v) => $v >= 3)
                ->where('activeProjects', fn ($v) => $v >= 2)
                ->where('secondsThisWeek', fn ($v) => $v >= 1800)
                ->where('secondsThisMonth', fn ($v) => $v >= 5400)
                ->has('topProjects')
                ->has('topClients')
        );
    }
}
