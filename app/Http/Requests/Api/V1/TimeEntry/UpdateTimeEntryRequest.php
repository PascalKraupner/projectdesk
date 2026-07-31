<?php

namespace App\Http\Requests\Api\V1\TimeEntry;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'started_at' => ['sometimes', 'date'],
            'duration_seconds' => ['sometimes', 'integer', 'min:1', 'max:604800'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
