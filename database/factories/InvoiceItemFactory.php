<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvoiceItem> */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = round(fake()->randomFloat(2, 0.25, 20), 2);
        $unitPrice = 80.00;

        return [
            'invoice_id' => Invoice::factory(),
            'project_id' => null,
            'description' => fake()->catchPhrase(),
            'service_date' => '2026-07-01',
            'quantity' => $quantity,
            'unit' => 'h',
            'unit_price' => $unitPrice,
            'amount' => round($quantity * $unitPrice, 2),
            'sort_order' => 0,
        ];
    }
}
