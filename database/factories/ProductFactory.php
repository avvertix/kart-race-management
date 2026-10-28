<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductKind;
use App\Models\Championship;
use App\Models\LicenceType;
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
            'kind' => ProductKind::LicenceEmission,
            'title' => fake()->randomElement([
                'Driver licence',
                'Driver licence renewal',
            ]),
            'description' => null,
            'championship_id' => null,
            'licence_type_id' => LicenceType::factory(),
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
            'licence_type_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
