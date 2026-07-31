<?php

namespace App\Http\Requests\Api\V1\Project;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'integer', 'exists:clients,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
        ];
    }
}
