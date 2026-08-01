<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use App\Services\ClientShareService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly ClientShareService $shareService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Client/Index', [
            'clients' => $this->clientService->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Client/Create', [
            'currencies' => $this->currencyOptions(),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = $this->clientService->create($request->validated());

        return redirect()->route('clients.show', $client);
    }

    public function show(Client $client): Response
    {
        $loaded = $this->clientService->find($client->id);

        return Inertia::render('Client/Show', [
            'client' => [
                'id' => $loaded->id,
                'name' => $loaded->name,
                'email' => $loaded->email,
                'contact_person' => $loaded->contact_person,
                'street' => $loaded->street,
                'postal_code' => $loaded->postal_code,
                'city' => $loaded->city,
                'country' => $loaded->country,
                'vat_id' => $loaded->vat_id,
                'hourly_rate' => $loaded->hourly_rate !== null ? (float) $loaded->hourly_rate : null,
                'currency' => $loaded->currency?->value,
                'created_at' => $loaded->created_at?->toIso8601String(),
                'total_seconds' => (int) ($loaded->total_seconds ?? 0),
                'total_seconds_this_month' => (int) ($loaded->total_seconds_this_month ?? 0),
                'projects' => $loaded->projects->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'status' => $p->status->value,
                    'total_seconds' => (int) ($p->total_seconds ?? 0),
                    'total_seconds_this_month' => (int) ($p->total_seconds_this_month ?? 0),
                ])->all(),
            ],
            'share_url' => $this->shareService->signedUrl($loaded),
            'share_expires_at' => $loaded->share_expires_at?->toIso8601String(),
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Client/Edit', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'contact_person' => $client->contact_person,
                'street' => $client->street,
                'postal_code' => $client->postal_code,
                'city' => $client->city,
                'country' => $client->country,
                'vat_id' => $client->vat_id,
                'hourly_rate' => $client->hourly_rate !== null ? (float) $client->hourly_rate : null,
                'currency' => $client->currency?->value,
            ],
            'currencies' => $this->currencyOptions(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->clientService->update($client, $request->validated());

        return redirect()->route('clients.show', $client);
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->clientService->delete($client);

        return redirect()->route('clients.index');
    }

    /** @return array<int, array{value: string, label: string}> */
    private function currencyOptions(): array
    {
        return array_map(
            fn (Currency $c) => ['value' => $c->value, 'label' => $c->value],
            Currency::cases(),
        );
    }
}
