<?php

namespace App\Http\Requests\TimeLog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreManualTimeLogRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'started_at' => ['required', 'date'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:604800'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
