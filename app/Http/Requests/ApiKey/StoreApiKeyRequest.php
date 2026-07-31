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
        return $this->filled('expires_at')
            ? CarbonImmutable::parse($this->validated('expires_at'))
            : null;
    }
}
