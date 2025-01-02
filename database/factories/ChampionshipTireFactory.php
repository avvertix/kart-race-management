<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChampionshipTire>
 */
class ChampionshipTireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'name' => fake()->name(),
            'price' => fake()->numberBetween(10000, 20000),
        ];
    }
}
