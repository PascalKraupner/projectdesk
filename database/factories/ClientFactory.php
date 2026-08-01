<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
        ];
    }

    public function billable(float $hourlyRate = 80.00): static
    {
        return $this->state(fn () => [
            'hourly_rate' => $hourlyRate,
            'currency' => 'EUR',
            'contact_person' => fake()->name(),
            'street' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
        ]);
    }
}
