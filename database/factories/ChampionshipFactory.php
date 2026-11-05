<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\EnrollmentSettingsData;
use App\Models\BonusMode;
use App\Models\Championship;
use App\Models\EnrollmentOption;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Championship>
 */
class ChampionshipFactory extends Factory
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
            'start_at' => Carbon::today()->startOfYear(),
            'end_at' => Carbon::today()->endOfYear(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
        ];
    }

    /**
     * A championship accepting enrollments in a period around now, with one enrollment option: free unless an amount
     * is given, optionally reserving a race number or covering the licence renewal. Tests needing more options create
     * them on top.
     */
    public function openForEnrollment(
        bool $reservesRaceNumber = false,
        bool $requiresCompetitor = false,
        int $amount = 0,
        bool $coversRenewal = false,
        ?CarbonImmutable $opensAt = null,
        ?CarbonImmutable $closesAt = null,
    ): static {
        return $this
            ->state(fn (array $attributes) => [
                'enrollment' => new EnrollmentSettingsData(
                    enabled: true,
                    opens_at: $opensAt ?? now()->subDay(),
                    closes_at: $closesAt ?? now()->addMonth(),
                    requires_competitor_when_underage: $requiresCompetitor,
                ),
            ])
            ->afterCreating(fn (Championship $championship) => EnrollmentOption::factory()->for($championship)->create([
                'title' => $amount === 0 ? 'Free enrollment' : 'Enrollment',
                'amount' => $amount,
                'reserves_race_number' => $reservesRaceNumber,
                'covers_licence_renewal' => $coversRenewal,
            ]));
    }

    /**
     * Create a championship with a race registration price.
     */
    public function priced(int $raceCost = 10000)
    {
        return $this->state(function (array $attributes) use ($raceCost) {
            return [
                'registration_price' => $raceCost,
            ];
        });
    }

    /**
     * Create a championship with specific bank account.
     */
    public function withBankAccount(?array $paymentSettings = null)
    {
        return $this->state(function (array $attributes) use ($paymentSettings) {
            return [
                'payment' => $paymentSettings ?? [
                    'bank_account' => '123456789',
                    'bank_name' => 'Test Bank',
                    'bank_holder' => 'Test Holder',
                ],
            ];
        });
    }

    /**
     * Create a championship with credit based bonus
     */
    public function withCredits(int $fixed_bonus_amount = 10000)
    {
        return $this->state(function (array $attributes) use ($fixed_bonus_amount) {
            return [
                'bonuses' => [
                    'fixed_bonus_amount' => $fixed_bonus_amount,
                ],
            ];
        });
    }

    /**
     * Create a championship with balance based bonus
     */
    public function withBalanceBonus()
    {
        return $this->state(function (array $attributes) {
            return [
                'bonuses' => [
                    'bonus_mode' => BonusMode::BALANCE,
                ],
            ];
        });
    }
}
