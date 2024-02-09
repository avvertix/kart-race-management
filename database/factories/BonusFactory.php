<?php

namespace Database\Factories;

use App\Models\BonusType;
use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bonus>
 */
class BonusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $licenceNumber = fake()->numerify();

        return [
            'championship_id' => Championship::factory(),
            'driver' => fake()->name() . ' ' .fake()->lastName(),
            'driver_licence' => $licenceNumber,
            'driver_licence_hash' => hash('sha512', $licenceNumber),
            'bonus_type' => BonusType::REGISTRATION_FEE,
            'amount' => 1,
        ];
    }
}
