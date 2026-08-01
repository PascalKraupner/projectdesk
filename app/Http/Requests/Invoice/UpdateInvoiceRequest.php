<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'issue_date' => ['sometimes', 'date'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
        ];
    }
}
