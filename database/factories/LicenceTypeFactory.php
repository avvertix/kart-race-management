<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\DriverLicence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenceType>
 */
class LicenceTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => LicenceProvider::Aci,
            'role' => LicenceRole::Driver,
            'name' => fake()->randomElement([
                'Driver licence',
                'Karting driver licence',
            ]),
            'description' => null,
            'race_licence_category' => null,
            'active' => true,
        ];
    }

    /**
     * An ASI driver licence, counted as national in race registration.
     */
    public function asi(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => LicenceProvider::Asi,
            'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
