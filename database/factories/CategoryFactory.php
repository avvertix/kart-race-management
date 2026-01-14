<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'championship_id' => Championship::factory(),
            'name' => fake()->randomElement([
                'Minikart',
                'Mini GR.3',
                '125 OK Senior',
            ]),
            'enabled' => true,
            'code' => null,
            'short_name' => null,
            'description' => null,
        ];
    }

    public function disabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'enabled' => false,
            ];
        });
    }

    public function withTire(?ChampionshipTire $tire = null)
    {
        return $this->state(function (array $attributes) {
            return [
                'championship_tire_id' => $tire ?? ChampionshipTire::factory()->state(['championship_id' => $attributes['championship_id']]),
            ];
        });
    }

    public function withTireState(array $state)
    {
        return $this->state(function (array $attributes) use ($state) {
            return [
                'championship_tire_id' => ChampionshipTire::factory()->state([
                    ...$state,
                    'championship_id' => $attributes['championship_id'],
                ]),
            ];
        });
    }

    public function withPrice(int $price)
    {
        return $this->state(function (array $attributes) use ($price) {
            return [
                'registration_price' => $price,
            ];
        });
    }
}
