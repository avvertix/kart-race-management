<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntitlementMode;
use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entitlement>
 */
class EntitlementFactory extends Factory
{
    /**
     * Define the model's default state: one race fee, for a licence number not on the platform.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'licence_hash' => hash('sha512', fake()->numerify('L#####')),
            'mode' => EntitlementMode::Credit,
            'amount' => 1,
            'remaining' => 1,
        ];
    }
}
