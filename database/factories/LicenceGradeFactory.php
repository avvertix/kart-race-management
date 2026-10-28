<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenceLevel;
use App\Models\LicenceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenceGrade>
 */
class LicenceGradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'licence_type_id' => LicenceType::factory(),
            'level' => LicenceLevel::National,
            'name' => fake()->randomElement(['C Junior', 'C Senior', 'D']),
            'sort' => 0,
        ];
    }
}
