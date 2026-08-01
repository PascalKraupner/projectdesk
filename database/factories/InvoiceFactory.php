<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $sequence = fake()->unique()->numberBetween(1, 999999);
        $issueDate = CarbonImmutable::parse('2026-07-01');

        return [
            'client_id' => Client::factory(),
            'number' => 'R'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT),
            'number_sequence' => $sequence,
            'status' => InvoiceStatus::Draft,
            'issue_date' => $issueDate->toDateString(),
            'payment_terms_days' => 14,
            'due_date' => $issueDate->addDays(14)->toDateString(),
            'currency' => 'EUR',
            'period_start' => $issueDate->startOfMonth()->toDateString(),
            'period_end' => $issueDate->endOfMonth()->toDateString(),
            'total_amount' => 0,
            'recipient_name' => fake()->company(),
            'recipient_contact_person' => fake()->name(),
            'recipient_street' => fake()->streetAddress(),
            'recipient_postal_code' => fake()->postcode(),
            'recipient_city' => fake()->city(),
        ];
    }

    public function issued(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Issued,
            'issued_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Paid,
            'issued_at' => now()->subDay(),
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
