<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class StatusPointsData extends Data
{
    public function __construct(
        public StatusPointsMode $mode = StatusPointsMode::Fixed,
        public float $points = 0,
    ) {}

    public function isRanked(): bool
    {
        return $this->mode === StatusPointsMode::Ranked;
    }
}
