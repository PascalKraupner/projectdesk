<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceItemRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'service_date' => ['nullable', 'date'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'unit' => ['nullable', 'string', 'max:16'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }
}
