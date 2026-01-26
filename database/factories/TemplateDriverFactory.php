<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Models\Sex;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TemplateDriver>
 */
class TemplateDriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $licenceNumber = fake()->numerify();
        $first_name = fake()->name();
        $last_name = fake()->lastName();

        return [
            'uuid' => Str::ulid(),
            'user_id' => User::factory(),
            'name' => "{$first_name} {$last_name}",
            'bib' => fake()->numberBetween(1, 200),

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
                'fiscal_code' => fake()->ssn(),
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
     * Indicate that the template has a competitor.
     *
     * @return Factory|TemplateDriverFactory
     */
    public function withCompetitor(): static
    {
        return $this->state(function (array $attributes) {
            $licenceNumber = fake()->numerify();

            return [
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
                    'fiscal_code' => fake()->ssn(),
                ],
            ];
        });
    }

    /**
     * Indicate that the template has a mechanic.
     *
     * @return Factory|TemplateDriverFactory
     */
    public function withMechanic(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'mechanic' => [
                    'name' => fake()->name(),
                    'licence_number' => fake()->numerify(),
                ],
            ];
        });
    }
}
