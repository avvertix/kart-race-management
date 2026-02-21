<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\PointsConfigData;
use App\Data\RunTypePointsData;
use App\Data\StatusPointsData;
use App\Data\StatusPointsMode;
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

    private function defaultPointsConfig(): PointsConfigData
    {
        $defaultStatuses = [
            ResultStatus::DID_NOT_START->value => new StatusPointsData(StatusPointsMode::Fixed, 0),
            ResultStatus::DID_NOT_FINISH->value => new StatusPointsData(StatusPointsMode::Fixed, 0),
            ResultStatus::DISQUALIFIED->value => new StatusPointsData(StatusPointsMode::Fixed, 0),
        ];

        return new PointsConfigData(runTypes: [
            RunType::QUALIFY->value => new RunTypePointsData(
                positions: [3, 2, 1],
                statuses: $defaultStatuses,
            ),
            RunType::RACE_1->value => new RunTypePointsData(
                positions: [25, 18, 15, 12, 10, 8, 6, 4, 2, 1],
                statuses: $defaultStatuses,
            ),
            RunType::RACE_2->value => new RunTypePointsData(
                positions: [25, 18, 15, 12, 10, 8, 6, 4, 2, 1],
                statuses: $defaultStatuses,
            ),
        ]);
    }
}
