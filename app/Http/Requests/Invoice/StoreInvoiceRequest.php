<?php

namespace App\Http\Requests\Invoice;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'client_id' => ['required_without:project_id', 'nullable', 'integer', 'exists:clients,id'],
            'project_id' => ['required_without:client_id', 'nullable', 'integer', 'exists:projects,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function from(): CarbonImmutable
    {
        return $this->filled('from')
            ? CarbonImmutable::parse($this->validated('from'), $this->timezone())->startOfDay()
            : $this->to()->startOfMonth();
    }

    public function to(): CarbonImmutable
    {
        return $this->filled('to')
            ? CarbonImmutable::parse($this->validated('to'), $this->timezone())->endOfDay()
            : CarbonImmutable::now($this->timezone())->endOfMonth();
    }

    private function timezone(): string
    {
        return config('app.display_timezone');
    }
}
