<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeLog\StoreManualTimeLogRequest;
use App\Http\Requests\TimeLog\StoreTimeLogRequest;
use App\Http\Requests\TimeLog\UpdateManualTimeLogRequest;
use App\Http\Requests\TimeLog\UpdateTimeLogRequest;
use App\Models\Project;
use App\Models\TimeLog;
use App\Services\TimeLogExportService;
use App\Services\TimeLogService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimeLogController extends Controller
{
    public function __construct(
        private readonly TimeLogService $timeLogService,
        private readonly TimeLogExportService $exportService,
    ) {}

    public function export(Project $project): StreamedResponse
    {
        return $this->exportService->streamProjectCsv($project);
    }

    public function store(StoreTimeLogRequest $request, Project $project): RedirectResponse
    {
        $this->timeLogService->start($project, $request->validated('note'));

        return back();
    }

    public function update(TimeLog $timeLog): RedirectResponse
    {
        $this->timeLogService->stop($timeLog);

        return back();
    }

    public function storeManual(StoreManualTimeLogRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $this->timeLogService->createManual(
            $project,
            $data['started_at'],
            $data['ended_at'],
            $data['note'] ?? null,
        );

        return back();
    }

    public function updateManual(UpdateManualTimeLogRequest $request, TimeLog $timeLog): RedirectResponse
    {
        $data = $request->validated();
        $this->timeLogService->updateManual(
            $timeLog,
            $data['started_at'],
            $data['ended_at'],
            $data['note'] ?? null,
        );

        return back();
    }

    public function updateNote(UpdateTimeLogRequest $request, TimeLog $timeLog): RedirectResponse
    {
        $this->timeLogService->updateNote($timeLog, $request->validated('note'));

        return back();
    }

    public function destroy(TimeLog $timeLog): RedirectResponse
    {
        $this->timeLogService->delete($timeLog);

        return back();
    }
}
