<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ResultStatus;
use App\Models\RunType;
use Spatie\LaravelData\Data;

class PointsConfigData extends Data
{
    /**
     * @param  array<int, RunTypePointsData>  $runTypes
     */
    public function __construct(
        public float $rainPercentage = 50,
        public float $smallCategoryPercentage = -50,
        public int $smallCategoryThreshold = 3,
        public array $runTypes = [],
    ) {}

    /**
     * Create from the form/JSON array format where RunType values are top-level keys.
     *
     * @param  array<int|string, array{positions?: array<int, float>, statuses?: array<int|string, array{mode?: string, points?: float}>}>  $data
     */
    public static function fromConfig(array $data): self
    {
        $runTypes = [];

        foreach ($data as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            $statuses = [];

            foreach ($config['statuses'] ?? [] as $statusValue => $statusConfig) {
                $statuses[(int) $statusValue] = new StatusPointsData(
                    mode: StatusPointsMode::from($statusConfig['mode'] ?? 'fixed'),
                    points: (float) ($statusConfig['points'] ?? 0),
                );
            }

            $runTypes[(int) $key] = new RunTypePointsData(
                positions: array_map(fn ($v) => (float) $v, $config['positions'] ?? []),
                statuses: $statuses,
            );
        }

        return new self(
            rainPercentage: (float) ($data['rain_percentage'] ?? 50),
            smallCategoryPercentage: (float) ($data['small_category_percentage'] ?? -50),
            smallCategoryThreshold: (int) ($data['small_category_threshold'] ?? 3),
            runTypes: $runTypes,
        );
    }

    public function getRunType(RunType $runType): RunTypePointsData
    {
        return $this->runTypes[$runType->value] ?? new RunTypePointsData;
    }

    public function getPointsForPosition(RunType $runType, int $position): float
    {
        return $this->getRunType($runType)->getPointsForPosition($position);
    }

    public function getPointsForStatus(RunType $runType, ResultStatus $status, ?int $position = null): float
    {
        $runTypeConfig = $this->getRunType($runType);
        $statusConfig = $runTypeConfig->getStatusConfig($status);

        if ($statusConfig->isRanked() && $position !== null) {
            return $runTypeConfig->getPointsForPosition($position);
        }

        return $statusConfig->points;
    }

    public function isStatusRanked(RunType $runType, ResultStatus $status): bool
    {
        return $this->getRunType($runType)->getStatusConfig($status)->isRanked();
    }

    /**
     * Convert to the flat array format used in forms and JSON storage.
     *
     * @return array<string, array{positions: list<float>, statuses: array<string, array{mode: string, points: float}>}>
     */
    public function toConfig(): array
    {
        $config = [
            'rain_percentage' => $this->rainPercentage,
            'small_category_percentage' => $this->smallCategoryPercentage,
            'small_category_threshold' => $this->smallCategoryThreshold,
        ];

        foreach ($this->runTypes as $runTypeValue => $runTypeData) {
            $statuses = [];

            foreach ($runTypeData->statuses as $statusValue => $statusData) {
                $statuses[(string) $statusValue] = [
                    'mode' => $statusData->mode->value,
                    'points' => $statusData->points,
                ];
            }

            $config[(string) $runTypeValue] = [
                'positions' => $runTypeData->positions,
                'statuses' => $statuses,
            ];
        }

        return $config;
    }
}
