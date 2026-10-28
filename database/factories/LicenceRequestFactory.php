<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\HolderData;
use App\Enums\LicenceLevel;
use App\Enums\LicenceRequestStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\LaravelData\DataCollection;

/**
 * A draft with nothing filled in. Use the StartLicenceRequest action for one with its Order.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenceRequest>
 */
class LicenceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'licence_type_id' => fn (array $attributes) => Product::query()->whereKey($attributes['product_id'])->value('licence_type_id'),
            'status' => LicenceRequestStatus::Draft,
            'order_id' => fn (array $attributes) => Order::factory()->state(['user_id' => $attributes['user_id']]),
            'parents' => HolderData::collect([], DataCollection::class),
        ];
    }

    /**
     * The licence and holder sections filled in, for an adult ACI national holder.
     */
    public function withLicenceAndHolder(?HolderData $holder = null): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => LicenceLevel::National,
            'grade_other' => 'C Senior',
            'holder' => $holder ?? LicenceFactory::holder(),
        ]);
    }

    /**
     * An underage holder with one parent.
     */
    public function underage(): static
    {
        $holder = LicenceFactory::holder('Luca', 'Rossi');
        $holder->birth_date = now()->subYears(12)->toDateString();

        return $this->withLicenceAndHolder($holder)->state(fn (array $attributes) => [
            'parents' => HolderData::collect([LicenceFactory::holder('Mario', 'Rossi')], DataCollection::class),
        ]);
    }

    public function submitted(): static
    {
        return $this->withLicenceAndHolder()->state(fn (array $attributes) => [
            'status' => LicenceRequestStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function changesRequested(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => LicenceRequestStatus::ChangesRequested,
        ]);
    }
}
