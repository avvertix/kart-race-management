<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\Competitor;
use App\Models\Driver;
use App\Models\DriverLicence;
use App\Models\Race;
use App\Models\Sex;
use Carbon\Carbon;
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
            'bib' => fake()->numberBetween(1, 200),
            'category' => 'category_key',
            'first_name' => fake()->name(),
            'last_name' => fake()->lastName(),
            'championship_id' => Championship::factory(),
            'race_id' => function (array $attributes) {
                return Race::factory(null, [
                    'championship_id' => $attributes['championship_id'],
                ]);
            },

            'driver_licence' => fake()->sha256(),

            'driver' => [
                'first_name' => function (array $attributes) {
                    return $attributes['first_name'];
                },
                'last_name' => function (array $attributes) {
                    return $attributes['first_name'];
                },
                
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
            ],

            'vehicles' => [
                [
                    'chassis_manufacturer' => '',
                    'engine_manufacturer' => '',
                    'engine_model' => '',
                    'oil_manufacturer' => '',
                    'oil_type' => '',
                    'oil_percentage' => fake()->numberBetween(1, 10),
                ],
            ],
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
