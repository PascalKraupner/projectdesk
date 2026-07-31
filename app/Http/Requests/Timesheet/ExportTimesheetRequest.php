<?php

namespace App\Http\Requests\Timesheet;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportTimesheetRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
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
