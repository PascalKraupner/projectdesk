<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreShareRequest;
use App\Models\Project;
use App\Services\ProjectService;
use App\Services\ProjectShareService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProjectShareController extends Controller
{
    public function __construct(
        private readonly ProjectShareService $shareService,
        private readonly ProjectService $projectService,
    ) {}

    public function show(Request $request, Project $project): Response|HttpResponse
    {
        $token = (string) $request->query('token', '');

        $valid = $request->hasValidSignature()
            && $project->share_token
            && hash_equals($project->share_token, $token);

        if (! $valid) {
            return Inertia::render('Project/PublicShareExpired')
                ->toResponse($request)
                ->setStatusCode(403);
        }

        $loaded = $this->projectService->find($project->id);

        return Inertia::render('Project/PublicShare', [
            'project' => [
                'title' => $loaded->title,
                'status' => $loaded->status->value,
                'total_seconds' => (int) ($loaded->total_seconds ?? 0),
            ],
        ]);
    }

    public function store(StoreShareRequest $request, Project $project): RedirectResponse
    {
        $expiresAt = CarbonImmutable::parse($request->validated('expires_at'));

        if ($request->boolean('regenerate')) {
            $this->shareService->regenerate($project, $expiresAt);
        } else {
            $this->shareService->enable($project, $expiresAt);
        }

        return back();
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->shareService->revoke($project);

        return back();
    }
}
