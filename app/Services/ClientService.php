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
