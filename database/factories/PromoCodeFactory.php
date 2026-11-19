<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntitlementMode;
use App\Enums\PromoCodeStatus;
use App\Models\Championship;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * Define the model's default state: an unclaimed code for one race fee.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => PromoCode::randomCode(),
            'championship_id' => Championship::factory(),
            'mode' => EntitlementMode::Credit,
            'amount' => 1,
            'status' => PromoCodeStatus::Unclaimed,
            'created_by' => User::factory()->organizer(),
        ];
    }

    public function claimed(): static
    {
        return $this->state([
            'status' => PromoCodeStatus::Claimed,
            'claimed_at' => now(),
            'claimed_by_user_id' => User::factory(),
            'licence_hash' => hash('sha512', fake()->numerify('L#####')),
        ]);
    }
}
