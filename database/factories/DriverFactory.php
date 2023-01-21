<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Sex;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
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
            'bib' => fake()->numberBetween(1, 100),
            'category' => 'category_key',
            'first_name' => fake()->name(),
            'last_name' => fake()->lastName(),
            'licence_type' => DriverLicence::LOCAL_CLUB,
            'licence_number' => fake()->numerify(),
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => new Carbon(fake()->dateTimeBetween('-20 years', '-18 years')),
            'birth_place' => fake()->city(),
            'medical_certificate_expiration_date' => new Carbon(fake()->dateTimeBetween('-2 months', 'today')),
            'residence_address' => fake()->address(),
            'sex' => Sex::UNSPECIFIED,
        ];
    }
}
