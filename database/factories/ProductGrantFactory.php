<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntitlementMode;
use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductGrant>
 */
class ProductGrantFactory extends Factory
{
    /**
     * Define the model's default state: one race fee in a championship.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'kind' => ProductGrantKind::Entitlement,
            'championship_id' => Championship::factory(),
            'mode' => EntitlementMode::Credit,
            'amount' => 1,
            'grants_bib_reservation' => false,
        ];
    }
}
