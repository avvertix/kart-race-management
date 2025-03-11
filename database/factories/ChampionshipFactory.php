<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Championship>
 */
class ChampionshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'uuid' => Str::ulid(),
            'start_at' => Carbon::today()->startOfYear(),
            'end_at' => Carbon::today()->endOfYear(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
        ];
    }

    /**
     * Create a championship with a race registration price.
     */
    public function priced(int $raceCost = 10000)
    {
        return $this->state(function (array $attributes) use ($raceCost) {
            return [
                'registration_price' => $raceCost,
            ];
        });
    }
}
