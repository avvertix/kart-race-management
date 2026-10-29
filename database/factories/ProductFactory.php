<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductKind;
use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => ProductKind::Package,
            'title' => fake()->randomElement([
                'Season package',
                'Early bird package',
            ]),
            'description' => null,
            'championship_id' => null,
            'active' => true,
        ];
    }

    /**
     * An enrollment in a championship.
     */
    public function enrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => ProductKind::Enrollment,
            'title' => 'Championship enrollment',
            'championship_id' => Championship::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
