<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
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

    public function show(Project $project): Response
    {
        return Inertia::render('Project/Show', [
            'project' => $this->projectService->find($project->id),
        ]);
    }
}
