<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Race;
use App\Models\RunType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RunResult>
 */
class RunResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'race_id' => Race::factory(),
            'run_type' => fake()->randomElement(RunType::cases())->value,
            'title' => fake()->sentence(3),
            'file_name' => fake()->word().'.xml',
        ];
    }

    /**
     * Indicate that the run result is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now(),
        ]);
    }
}
