<?php

namespace App\Http\Requests\Client;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
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
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($this->route('client')),
            ],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'currency' => ['required', Rule::in(array_column(Currency::cases(), 'value'))],
        ];
    }
}
