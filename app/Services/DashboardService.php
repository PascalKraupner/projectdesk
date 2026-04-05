<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Project;

class DashboardService
{
    public function getStats(): array
    {
        return [
            'totalClients' => Client::count(),
            'totalProjects' => Project::count(),
        ];
    }
}
