<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Sex;
use App\Models\User;
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
        $licence = fake()->numerify();

        return [
            'championship_id' => Championship::factory(),
            'bib' => fake()->numberBetween(1, 100),
            'code' => substr(fake()->md5(), 0, 8),
            'first_name' => fake()->name(),
            'last_name' => fake()->lastName(),
            'user_id' => User::factory(),

            'licence_hash' => hash('sha512', $licence),

            'birth_date_hash' => hash('sha512', '1999-11-11'),

            'licence_type' => DriverLicence::LOCAL_NATIONAL,
            'licence_number' => $licence,
            
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),

            'fiscal_code' => fake()->ssn(),

            'birth' => [
                'date' => Carbon::parse('1999-11-11'),
                'place' => fake()->city(),
            ],
            
            'medical_certificate_expiration_date' => new Carbon(fake()->dateTimeBetween('-2 months', 'today')),
            'address' =>[
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ] ,
        ];
    }
}
