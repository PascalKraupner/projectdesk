<?php

namespace App\Http\Requests\Api\V1\Project;

use App\Http\Requests\Api\V1\PaginationRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexProjectRequest extends PaginationRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'client_id' => ['sometimes', 'integer', 'exists:clients,id'],
        ];
    }

    public function clientId(): ?int
    {
        return $this->filled('client_id') ? (int) $this->validated('client_id') : null;
    }
}
