<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TimeEntry\IndexTimeEntryRequest;
use App\Http\Requests\Api\V1\TimeEntry\StoreTimeEntryRequest;
use App\Http\Requests\Api\V1\TimeEntry\UpdateTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeLog;
use App\Services\TimeLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TimeEntryController extends Controller
{
    public function __construct(
        private readonly TimeLogService $timeLogService,
    ) {}

    public function index(IndexTimeEntryRequest $request): AnonymousResourceCollection
    {
        return TimeEntryResource::collection(
            $this->timeLogService->paginate($request->perPage(), $request->filters()),
        );
    }

    public function store(StoreTimeEntryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $entry = $this->timeLogService->createManualForProject(
            (int) $data['project_id'],
            $data['started_at'],
            (int) $data['duration_seconds'],
            $data['note'] ?? null,
        );

        return TimeEntryResource::make($entry)->response()->setStatusCode(201);
    }

    public function show(TimeLog $timeEntry): TimeEntryResource
    {
        return TimeEntryResource::make($timeEntry);
    }

    public function update(UpdateTimeEntryRequest $request, TimeLog $timeEntry): TimeEntryResource
    {
        return TimeEntryResource::make(
            $this->timeLogService->updateManualPartial($timeEntry, $request->validated()),
        );
    }

    public function destroy(TimeLog $timeEntry): Response
    {
        $this->timeLogService->delete($timeEntry);

        return response()->noContent();
    }
}
