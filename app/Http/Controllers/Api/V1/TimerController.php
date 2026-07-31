<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TimeEntry\StartTimerRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\Project;
use App\Models\TimeLog;
use App\Services\TimeLogService;
use Illuminate\Http\JsonResponse;

class TimerController extends Controller
{
    public function __construct(
        private readonly TimeLogService $timeLogService,
    ) {}

    public function current(): JsonResponse
    {
        $entry = $this->timeLogService->current();

        return response()->json([
            'data' => $entry ? TimeEntryResource::make($entry)->resolve() : null,
        ]);
    }

    public function start(StartTimerRequest $request, Project $project): JsonResponse
    {
        $entry = $this->timeLogService->start($project, $request->validated('note'));

        return TimeEntryResource::make($entry)->response()->setStatusCode(201);
    }

    public function pause(TimeLog $timeEntry): TimeEntryResource
    {
        return TimeEntryResource::make($this->timeLogService->pause($timeEntry));
    }

    public function resume(TimeLog $timeEntry): TimeEntryResource
    {
        return TimeEntryResource::make($this->timeLogService->resume($timeEntry));
    }

    public function stop(TimeLog $timeEntry): TimeEntryResource
    {
        return TimeEntryResource::make($this->timeLogService->stop($timeEntry));
    }
}
