<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Project/Index', [
            'projects' => $this->projectService->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Project/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(
                fn (ProjectStatus $s) => ['value' => $s->value, 'label' => ucfirst($s->value)],
                ProjectStatus::cases(),
            ),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->projectService->create($request->validated());

        return redirect()->route('projects.index');
    }

    public function show(Project $project): Response
    {
        return Inertia::render('Project/Show', [
            'project' => $this->projectService->find($project->id),
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Project/Edit', [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(
                fn (ProjectStatus $s) => ['value' => $s->value, 'label' => ucfirst($s->value)],
                ProjectStatus::cases(),
            ),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->projectService->update($project, $request->validated());

        return redirect()->route('projects.show', $project);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->projectService->delete($project);

        return redirect()->route('projects.index');
    }
}
