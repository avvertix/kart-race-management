<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\EnrollmentOption>
 */
class EnrollmentOptionFactory extends Factory
{
    /**
     * Define the model's default state: a free option for any licence.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'title' => 'Enrollment',
            'amount' => 0,
        ];
    }

    /**
     * A paid option covering the licence renewal, like BKQ's.
     */
    public function coveringRenewal(int $amount = 18000): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'covers_licence_renewal' => true,
        ]);
    }
}
