<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChampionshipPenalty>
 */
class ChampionshipPenaltyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'title' => fake()->words(3, asText: true),
            'description' => fake()->sentence(),
        ];
    }
}
