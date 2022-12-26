<?php

namespace Database\Factories;

use App\Models\Championship;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Race>
 */
class RaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $start = fake()->dateTimeBetween('today', '+1 month');

        return [
            'uuid' => Str::ulid(),
            'event_start_at' => (new Carbon($start))->startOfDay(),
            'event_end_at' => (new Carbon($start))->endOfDay(),
            'track' => fake()->city(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'tags' => [],
            'properties' => [],
            'championship_id' => Championship::factory(),
        ];
    }
}
