<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceItemRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'string', 'max:255'],
            'service_date' => ['sometimes', 'date'],
            'quantity' => ['sometimes', 'numeric', 'min:0', 'max:99999.99'],
            'unit' => ['sometimes', 'string', 'max:16'],
            'unit_price' => ['sometimes', 'numeric', 'min:0', 'max:99999.99'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }
}
