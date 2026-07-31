<?php

namespace App\Http\Requests\Api\V1\Project;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => ProjectStatus::Active->value]);
        }
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ];
    }
}
