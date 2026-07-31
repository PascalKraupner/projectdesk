<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($this->route('client')),
            ],
            'hourly_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'currency' => ['sometimes', Rule::enum(Currency::class)],
        ];
    }
}
