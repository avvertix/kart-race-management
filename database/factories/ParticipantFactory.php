<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\Competitor;
use App\Models\Driver;
use App\Models\Race;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Participant>
 */
class ParticipantFactory extends Factory
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
            'driver_id' => Driver::factory(),
            'competitor_id' => Competitor::factory(),
            'bib' => fake()->numberBetween(0, 200),
            'category' => function (array $attributes) {
                return Driver::find($attributes['driver_id'])->category;
            },
            'first_name' => function (array $attributes) {
                return Driver::find($attributes['driver_id'])->first_name;
            },
            'last_name' => function (array $attributes) {
                return Driver::find($attributes['driver_id'])->last_name;
            },
            'championship_id' => Championship::factory(),
            'race_id' => function (array $attributes) {
                return Race::factory(null, [
                    'championship_id' => $attributes['championship_id'],
                ]);
            },
        ];
    }


    /**
     * Indicate that the participant is confirmed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'confirmed_at' => now(),
            ];
        });
    }
}
