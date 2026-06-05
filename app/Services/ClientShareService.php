<?php

namespace App\Services;

use App\Models\Client;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ClientShareService
{
    public function enable(Client $client, CarbonImmutable $expiresAt): Client
    {
        $client->update([
            'share_token' => $client->share_token ?? Str::random(40),
            'share_expires_at' => $expiresAt,
        ]);

        return $client;
    }

    public function regenerate(Client $client, CarbonImmutable $expiresAt): Client
    {
        $client->update([
            'share_token' => Str::random(40),
            'share_expires_at' => $expiresAt,
        ]);

        return $client;
    }

    public function revoke(Client $client): Client
    {
        $client->update([
            'share_token' => null,
            'share_expires_at' => null,
        ]);

        return $client;
    }

    public function signedUrl(Client $client): ?string
    {
        if (! $client->share_token || ! $client->share_expires_at) {
            return null;
        }

        return URL::temporarySignedRoute(
            'clients.share',
            $client->share_expires_at,
            ['client' => $client->id, 'token' => $client->share_token],
        );
    }
}
