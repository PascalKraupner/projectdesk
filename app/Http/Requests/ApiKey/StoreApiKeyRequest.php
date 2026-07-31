<?php

namespace App\Http\Requests\ApiKey;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function expiresAt(): ?CarbonImmutable
    {
        if (! $this->filled('expires_at')) {
            return null;
        }

        // End of the chosen day in the display timezone: a date-only value would
        // otherwise parse to midnight, so picking "1 September" would kill the key
        // as that day began rather than letting it work through it.
        // ->utc() because Eloquent formats a Carbon in its own timezone, so a
        // Berlin-zoned value would be stored with its wall-clock digits and read
        // back as UTC, drifting by the offset.
        return CarbonImmutable::parse(
            $this->validated('expires_at'),
            config('app.display_timezone'),
        )->endOfDay()->utc();
    }
}
