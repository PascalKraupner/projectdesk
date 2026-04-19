<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use Illuminate\Support\Carbon;

class DashboardService
{
    /** @return array<string, mixed> */
    public function getStats(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'totalClients' => Client::count(),
            'totalProjects' => Project::count(),
            'activeProjects' => Project::where('status', ProjectStatus::Active)->count(),
            'secondsThisWeek' => (int) TimeLog::where('started_at', '>=', $startOfWeek)->sum('duration_seconds'),
            'secondsThisMonth' => (int) TimeLog::where('started_at', '>=', $startOfMonth)->sum('duration_seconds'),
            'topProjects' => Project::withSum('timeLogs as total_seconds', 'duration_seconds')
                ->with('client:id,name')
                ->orderByDesc('total_seconds')
                ->limit(5)
                ->get()
                ->filter(fn (Project $p) => (int) $p->total_seconds > 0)
                ->values(),
            'topClients' => Client::withSum('timeLogs as total_seconds', 'duration_seconds')
                ->orderByDesc('total_seconds')
                ->limit(5)
                ->get()
                ->filter(fn (Client $c) => (int) $c->total_seconds > 0)
                ->values(),
        ];
    }
}
