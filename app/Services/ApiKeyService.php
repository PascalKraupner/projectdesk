<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyService
{
    /** @return Collection<int, PersonalAccessToken> */
    public function forUser(User $user): Collection
    {
        return $user->tokens()->latest()->get();
    }

    /** Returns the plaintext key, which is never recoverable afterwards. */
    public function create(User $user, string $name, ?CarbonImmutable $expiresAt = null): string
    {
        return $user->createToken($name, ['*'], $expiresAt)->plainTextToken;
    }

    public function revoke(User $user, int $id): bool
    {
        return $user->tokens()->whereKey($id)->delete() > 0;
    }
}
