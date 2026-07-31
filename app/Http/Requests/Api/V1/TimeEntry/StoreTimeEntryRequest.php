<?php

namespace App\Http\Requests\Api\V1\TimeEntry;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'started_at' => ['required', 'date'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:604800'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
