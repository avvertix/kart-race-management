<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\CompetitorLicence;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Competitor>
 */
class CompetitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'championship_id' => Championship::factory(),
            'name' => fake()->name() . ' ' . fake()->lastName(),
            'licence_type' => CompetitorLicence::LOCAL,
            'licence_number' => fake()->numerify(),
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => new Carbon(fake()->dateTimeBetween('-20 years', '-18 years')),
            'birth_place' => fake()->city(),
            'residence_address' => fake()->address(),
        ];
    }
}
