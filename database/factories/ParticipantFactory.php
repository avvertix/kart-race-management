<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bonus;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Competitor;
use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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

    public function driver($driver)
    {
        return $this->state(function (array $attributes) use ($driver) {

            $licenceNumber = fake()->numerify();

            $first_name = fake()->name();
            $last_name = fake()->lastName();

            return [
                'bib' => $driver['bib'] ?? fake()->numberBetween(1, 200),
                'first_name' => $driver['first_name'] ?? $first_name,
                'last_name' => $driver['last_name'] ?? $last_name,
                'driver_licence' => hash('sha512', $driver['licence_number'] ?? $licenceNumber),
                'licence_type' => $driver['licence_type'] ?? DriverLicence::LOCAL_NATIONAL,
                'driver' => array_merge([
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
                ], $driver),
            ];
        });
    }

    /**
     * Indicate that the participant is confirmed.
     *
     * @return Factory|ParticipantFactory
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
     * @return Factory|ParticipantFactory
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
     * @return Factory|ParticipantFactory
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
                    'fiscal_code' => fake()->ssn(),
                ],
            ];
        });
    }

    /**
     * Indicate that the participant has a mechanic.
     *
     * @return Factory|ParticipantFactory
     */
    public function withMechanic()
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

    /**
     * Indicate that the participant requested to use the bonus.
     *
     * @return Factory|ParticipantFactory
     */
    public function usingCredits(?Bonus $bonus = null, int $amount = 1)
    {
        return $this->state(function (array $attributes) {

            return [
                'use_bonus' => true,
            ];
        })->afterCreating(function (Participant $participant) use ($bonus, $amount) {
            $useBonus = filled($bonus) ? $bonus : Bonus::factory()->recycle($participant->championship)->create();

            collect(range(1, $amount))->each(fn () => $participant->bonuses()->attach($useBonus));
        });
    }

    public function usingBalance(?Bonus $bonus = null, int $available_amount = 8500, int $used_amount = 1000)
    {
        return $this->state(function (array $attributes) {

            return [
                'use_bonus' => true,
            ];
        })->afterCreating(function (Participant $participant) use ($bonus, $available_amount, $used_amount) {
            $useBonus = filled($bonus) ? $bonus : Bonus::factory()->recycle($participant->championship)->create(['amount' => $available_amount]);

            $participant->bonuses()->attach($useBonus, ['amount' => $used_amount]);
        });
    }

    /**
     * Indicate that the participant requested to use the bonus.
     *
     * @return Factory|ParticipantFactory
     */
    public function category(?Category $category = null)
    {
        return $this->state(function (array $attributes) use ($category) {

            return [
                'category_id' => optional($category)->getKey() ?? Category::factory()->withTire(),
                'category' => optional($category)->ulid ?? 'category_key',
            ];
        });
    }
}
