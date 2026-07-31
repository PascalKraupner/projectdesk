<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiKey\StoreApiKeyRequest;
use App\Services\ApiKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyController extends Controller
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Settings/ApiKeys', [
            'keys' => $this->apiKeyService->forUser($request->user())
                ->map(fn (PersonalAccessToken $key) => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'expires_at' => $key->expires_at?->toIso8601String(),
                    'created_at' => $key->created_at?->toIso8601String(),
                ])->all(),
        ]);
    }

    public function store(StoreApiKeyRequest $request): RedirectResponse
    {
        $plainTextKey = $this->apiKeyService->create(
            $request->user(),
            $request->validated('name'),
            $request->expiresAt(),
        );

        return back()->with('apiKey', $plainTextKey);
    }

    public function destroy(Request $request, int $apiKey): RedirectResponse
    {
        $this->apiKeyService->revoke($request->user(), $apiKey);

        return back();
    }
}
