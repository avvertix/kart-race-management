<?php

namespace App\Data\Results;

use App\Models\ResultStatus;
use Spatie\LaravelData\Data;

class RacerQualifyingResultData extends Data
{
    public function __construct(
        public readonly int $bib,
        public readonly ResultStatus $status,
        public readonly string $name,
        public readonly string $category,
        public readonly string $position, // can be number, DSQ, DNF, DNS
        public readonly string $position_in_category, // can be number, DSQ, DNF, DNS
        public readonly string $best_lap_time,
        public readonly string $best_lap_number,
        public readonly string $gap_from_leader,
        public readonly string $gap_from_previous,
        public readonly string $racer_hash,
        public readonly ?string $second_best_time = null,
        public readonly ?string $second_best_lap_number = null,
        public readonly ?float $best_speed = null,
        public readonly ?float $second_best_speed = null,
        public readonly ?float $points = null,
        public readonly bool $is_dnf = false,
        public readonly bool $is_dns = false,
        public readonly bool $is_dq = false,
    ) {}
}
