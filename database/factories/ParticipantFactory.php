<?php

namespace Database\Factories;

use App\Models\Championship;
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
            'bib' => fake()->numberBetween(0, 200),
            'category' => 'mini',
            'name' => fake()->name(),
            'surname' => fake()->lastName(),
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
