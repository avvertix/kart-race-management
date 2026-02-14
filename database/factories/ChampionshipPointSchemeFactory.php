<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Championship;
use App\Models\ResultStatus;
use App\Models\RunType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChampionshipPointScheme>
 */
class ChampionshipPointSchemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'name' => fake()->word(),
            'points_config' => $this->defaultPointsConfig(),
        ];
    }

    /**
     * @return array<int, array{positions: list<int>, statuses: array<int, array{mode: string, points: int}>}>
     */
    private function defaultPointsConfig(): array
    {
        $defaultStatuses = [
            ResultStatus::DID_NOT_START->value => ['mode' => 'fixed', 'points' => 0],
            ResultStatus::DID_NOT_FINISH->value => ['mode' => 'fixed', 'points' => 0],
            ResultStatus::DISQUALIFIED->value => ['mode' => 'fixed', 'points' => 0],
        ];

        return [
            RunType::QUALIFY->value => [
                'positions' => [3, 2, 1],
                'statuses' => $defaultStatuses,
            ],
            RunType::RACE_1->value => [
                'positions' => [25, 18, 15, 12, 10, 8, 6, 4, 2, 1],
                'statuses' => $defaultStatuses,
            ],
            RunType::RACE_2->value => [
                'positions' => [25, 18, 15, 12, 10, 8, 6, 4, 2, 1],
                'statuses' => $defaultStatuses,
            ],
        ];
    }
}
