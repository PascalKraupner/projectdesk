<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientService
{
    public function all(): Collection
    {
        return Client::withCount('projects')->latest()->get();
    }
}
