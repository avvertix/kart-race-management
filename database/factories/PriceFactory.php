<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Price>
 */
class PriceFactory extends Factory
{
    /**
     * Define the model's default state: a fixed amount valid since yesterday, until further notice.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'amount' => 18000,
            'amount_min' => null,
            'amount_max' => null,
            'currency' => Currency::EUR,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
        ];
    }

    /**
     * A range, in cents, the organizer settles for each Order.
     */
    public function range(int $min = 23000, int $max = 31000): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => null,
            'amount_min' => $min,
            'amount_max' => $max,
        ]);
    }
}
