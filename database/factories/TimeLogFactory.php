<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TimeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TimeLog> */
class TimeLogFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', '-1 day');
        $duration = fake()->numberBetween(300, 7200);
        $endedAt = (clone $startedAt)->modify("+{$duration} seconds");

        return [
            'project_id' => Project::factory(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $duration,
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'started_at' => now(),
            'ended_at' => null,
            'duration_seconds' => null,
        ]);
    }
}
