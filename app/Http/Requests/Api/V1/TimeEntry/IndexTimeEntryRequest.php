<?php

namespace App\Http\Requests\Api\V1\TimeEntry;

use App\Http\Requests\Api\V1\PaginationRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class IndexTimeEntryRequest extends PaginationRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
            'client_id' => ['sometimes', 'integer', 'exists:clients,id'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'project_id' => $this->validated('project_id'),
            'client_id' => $this->validated('client_id'),
            'from' => $this->validated('from'),
            'to' => $this->validated('to'),
        ];
    }
}
