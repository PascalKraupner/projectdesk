<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Project\IndexProjectRequest;
use App\Http\Requests\Api\V1\Project\StoreProjectRequest;
use App\Http\Requests\Api\V1\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(IndexProjectRequest $request): AnonymousResourceCollection
    {
        return ProjectResource::collection(
            $this->projectService->paginate($request->perPage(), $request->clientId()),
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return ProjectResource::make($project)->response()->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        return ProjectResource::make($this->projectService->findWithTotals($project->id));
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        return ProjectResource::make($this->projectService->update($project, $request->validated()));
    }

    public function destroy(Project $project): Response
    {
        $this->projectService->delete($project);

        return response()->noContent();
    }
}
