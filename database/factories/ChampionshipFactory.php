<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
}
