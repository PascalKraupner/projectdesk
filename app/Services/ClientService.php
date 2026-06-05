<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientService
{
    public function all(): Collection
    {
        return Client::withCount('projects')
            ->withSum('timeLogs as total_seconds', 'duration_seconds')
            ->latest()
            ->get();
    }

    public function find(int $id): Client
    {
        $startOfMonth = now()->startOfMonth();

        return Client::with([
            'projects' => fn ($q) => $q
                ->withSum('timeLogs as total_seconds', 'duration_seconds')
                ->withSum([
                    'timeLogs as total_seconds_this_month' => fn ($l) => $l->where('started_at', '>=', $startOfMonth),
                ], 'duration_seconds')
                ->with(['timeLogs' => fn ($l) => $l->latest('started_at')])
                ->orderBy('title'),
        ])
            ->withSum('timeLogs as total_seconds', 'duration_seconds')
            ->withSum([
                'timeLogs as total_seconds_this_month' => fn ($l) => $l->where('started_at', '>=', $startOfMonth),
            ], 'duration_seconds')
            ->findOrFail($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Client
    {
        return Client::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client;
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }
}
