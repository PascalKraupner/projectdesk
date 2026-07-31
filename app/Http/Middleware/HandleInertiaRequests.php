<?php

namespace App\Http\Middleware;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'runningTimer' => fn () => $this->runningTimer($request),
            'timerProjects' => fn () => $this->timerProjects($request),
            'flash' => [
                // Plaintext API key, flashed once right after creation and never stored.
                'apiKey' => $request->session()->get('apiKey'),
            ],
        ];
    }

    /** @return array{id: int, project_id: int, project_title: ?string, started_at: string, last_resumed_at: ?string, duration_seconds: int, paused: bool}|null */
    private function runningTimer(Request $request): ?array
    {
        if (! $request->user()) {
            return null;
        }

        $log = TimeLog::active()
            ->with('project:id,title')
            ->latest('started_at')
            ->first();

        if (! $log) {
            return null;
        }

        return [
            'id' => $log->id,
            'project_id' => $log->project_id,
            'project_title' => $log->project?->title,
            'started_at' => $log->started_at?->toIso8601String(),
            'last_resumed_at' => $log->last_resumed_at?->toIso8601String(),
            'duration_seconds' => (int) ($log->duration_seconds ?? 0),
            'paused' => $log->last_resumed_at === null,
        ];
    }

    /** @return array<int, array{id: int, title: string, client_name: ?string}> */
    private function timerProjects(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return Project::query()
            ->where('status', ProjectStatus::Active)
            ->with('client:id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'client_id'])
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'client_name' => $p->client?->name,
            ])
            ->all();
    }
}
