<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ResultStatus;
use Spatie\LaravelData\Data;

class RunTypePointsData extends Data
{
    /**
     * @param  array<int, float>  $positions
     * @param  array<int, StatusPointsData>  $statuses
     */
    public function __construct(
        public array $positions = [],
        public array $statuses = [],
    ) {}

    public function getPointsForPosition(int $position): float
    {
        if ($position < 1) {
            return 0;
        }

        return (float) ($this->positions[$position - 1] ?? 0);
    }

    public function getStatusConfig(ResultStatus $status): StatusPointsData
    {
        return $this->statuses[$status->value] ?? new StatusPointsData;
    }
}
