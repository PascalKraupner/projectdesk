<?php

namespace App\Http\Controllers;

use App\Http\Requests\Timesheet\ExportTimesheetRequest;
use App\Models\Client;
use App\Models\Project;
use App\Services\TimesheetService;
use Symfony\Component\HttpFoundation\Response;

class TimesheetController extends Controller
{
    public function __construct(
        private readonly TimesheetService $timesheetService,
    ) {}

    public function client(ExportTimesheetRequest $request, Client $client): Response
    {
        return $this->timesheetService->clientPdf($client, $request->from(), $request->to());
    }

    public function project(ExportTimesheetRequest $request, Project $project): Response
    {
        return $this->timesheetService->projectPdf($project, $request->from(), $request->to());
    }
}
