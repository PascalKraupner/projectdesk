<?php

namespace App\Http\Middleware;

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
        ];
    }

    /** @return array{id: int, project_id: int, project_title: ?string, started_at: string}|null */
    private function runningTimer(Request $request): ?array
    {
        if (! $request->user()) {
            return null;
        }

        $log = TimeLog::running()
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
        ];
    }
}
