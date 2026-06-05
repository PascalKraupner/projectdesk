<?php

namespace App\Http\Requests\Client;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('currency')) {
            $this->merge(['currency' => Currency::EUR->value]);
        }
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clients,email'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'currency' => ['required', Rule::in(array_column(Currency::cases(), 'value'))],
        ];
    }
}
