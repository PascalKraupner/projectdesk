<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreShareRequest;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\ClientShareService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ClientShareController extends Controller
{
    public function __construct(
        private readonly ClientShareService $shareService,
        private readonly ClientService $clientService,
    ) {}

    public function show(Request $request, Client $client): Response|HttpResponse
    {
        $token = (string) $request->query('token', '');

        $valid = $request->hasValidSignature()
            && $client->share_token
            && hash_equals($client->share_token, $token);

        if (! $valid) {
            return Inertia::render('Client/PublicShareExpired')
                ->toResponse($request)
                ->setStatusCode(403);
        }

        $loaded = $this->clientService->find($client->id);
        $startOfMonth = now()->startOfMonth();

        $projects = $loaded->projects
            ->filter(fn ($project) => (int) ($project->total_seconds_this_month ?? 0) > 0)
            ->values()
            ->map(fn ($project) => [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status->value,
                'total_seconds' => (int) ($project->total_seconds_this_month ?? 0),
                'time_logs' => $project->timeLogs
                    ->filter(fn ($log) => $log->ended_at !== null && $log->started_at >= $startOfMonth)
                    ->values()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'started_at' => $log->started_at?->toIso8601String(),
                        'ended_at' => $log->ended_at?->toIso8601String(),
                        'duration_seconds' => (int) ($log->duration_seconds ?? 0),
                        'note' => $log->note,
                    ])->all(),
            ])->all();

        return Inertia::render('Client/PublicShare', [
            'client' => [
                'name' => $loaded->name,
                'hourly_rate' => $loaded->hourly_rate !== null ? (float) $loaded->hourly_rate : null,
                'currency' => $loaded->currency?->value,
                'total_seconds' => (int) ($loaded->total_seconds_this_month ?? 0),
                'period_label' => $startOfMonth->isoFormat('MMMM YYYY'),
                'projects' => $projects,
            ],
        ]);
    }

    public function store(StoreShareRequest $request, Client $client): RedirectResponse
    {
        $expiresAt = CarbonImmutable::parse($request->validated('expires_at'));

        if ($request->boolean('regenerate')) {
            $this->shareService->regenerate($client, $expiresAt);
        } else {
            $this->shareService->enable($client, $expiresAt);
        }

        return back();
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->shareService->revoke($client);

        return back();
    }
}
