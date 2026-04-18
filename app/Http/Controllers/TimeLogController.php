<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeLog\UpdateTimeLogRequest;
use App\Models\Project;
use App\Models\TimeLog;
use App\Services\TimeLogService;
use Illuminate\Http\RedirectResponse;

class TimeLogController extends Controller
{
    public function __construct(
        private readonly TimeLogService $timeLogService,
    ) {}

    public function store(Project $project): RedirectResponse
    {
        $this->timeLogService->start($project);

        return back();
    }

    public function update(UpdateTimeLogRequest $request, TimeLog $timeLog): RedirectResponse
    {
        $this->timeLogService->stop($timeLog, $request->validated('note'));

        return back();
    }

    public function destroy(TimeLog $timeLog): RedirectResponse
    {
        $this->timeLogService->delete($timeLog);

        return back();
    }
}
