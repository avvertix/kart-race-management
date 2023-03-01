<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\Competitor;
use App\Models\CompetitorLicence;
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
        $licenceNumber = fake()->numerify();

        $first_name = fake()->name();
        $last_name = fake()->lastName();

        return [
            'uuid' => Str::ulid(),
            'bib' => fake()->numberBetween(1, 200),
            'category' => 'category_key',
            'first_name' => $first_name,
            'last_name' => $last_name,
            'championship_id' => Championship::factory(),
            'race_id' => function (array $attributes) {
                return Race::factory(null, [
                    'championship_id' => $attributes['championship_id'],
                ]);
            },

            'driver_licence' => hash('sha512', $licenceNumber),

            'licence_type' => DriverLicence::LOCAL_NATIONAL,

            'driver' => [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'licence_type' => DriverLicence::LOCAL_NATIONAL,
                'licence_number' => $licenceNumber,
                'licence_renewed_at' => null,
                'nationality' => 'Italy',
                'email' => fake()->email(),
                'phone' => fake()->phoneNumber(),
                'birth_date' => new Carbon(fake()->dateTimeBetween('-20 years', '-18 years')),
                'birth_place' => fake()->city(),
                'medical_certificate_expiration_date' => new Carbon(fake()->dateTimeBetween('-2 months', 'today')),
                'residence_address' => [
                    'address' => 'via dei Platani, 40',
                    'city' => 'Milan',
                    'province' => 'Milan',
                    'postal_code' => '20146',
                ],
                'sex' => Sex::UNSPECIFIED,
            ],

            'vehicles' => [
                [
                    'chassis_manufacturer' => 'Birel',
                    'engine_manufacturer' => 'Iame',
                    'engine_model' => 'X30',
                    'oil_manufacturer' => 'Shell',
                    'oil_type' => 'Oil type',
                    'oil_percentage' => fake()->numberBetween(1, 10),
                ],
            ],
        ];
    }


    /**
     * Indicate that the participant is confirmed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|\Database\Factories\ParticipantFactory
     */
    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'confirmed_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the participant registratio is completed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|\Database\Factories\ParticipantFactory
     */
    public function markCompleted()
    {
        return $this->state(function (array $attributes) {
            return [
                'registration_completed_at' => now(),
            ];
        });
    }
    
    /**
     * Indicate that the participant has a competitor.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|\Database\Factories\ParticipantFactory
     */
    public function withCompetitor()
    {
        return $this->state(function (array $attributes) {

            $licenceNumber = fake()->numerify();

            return [
                'competitor_licence' => hash('sha512', $licenceNumber),

                'competitor' => [
                    'first_name' => fake()->name(),
                    'last_name' => fake()->lastName(),
                    'licence_type' => CompetitorLicence::LOCAL,
                    'licence_number' => $licenceNumber,
                    'licence_renewed_at' => null,
                    'nationality' => 'Italy',
                    'email' => fake()->email(),
                    'phone' => fake()->phoneNumber(),
                    'birth_date' => new Carbon(fake()->dateTimeBetween('-20 years', '-18 years')),
                    'birth_place' => fake()->city(),
                    'residence_address' => [
                        'address' => 'via dei Platani, 40',
                        'city' => 'Milan',
                        'province' => 'Milan',
                        'postal_code' => '20146',
                    ],
                ]
            ];
        });
    }
    
    /**
     * Indicate that the participant has a mechanic.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|\Database\Factories\ParticipantFactory
     */
    public function withMechanic()
    {
        return $this->state(function (array $attributes) {

            return [
                'mechanic' => [
                    'name' => fake()->name(),
                    'licence_number' => fake()->numerify(),
                ]
            ];
        });
    }
    
    /**
     * Indicate that the participant requested to use the bonus.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|\Database\Factories\ParticipantFactory
     */
    public function usingBonus()
    {
        return $this->state(function (array $attributes) {

            return [
                'use_bonus' => true,
            ];
        });
    }
}
