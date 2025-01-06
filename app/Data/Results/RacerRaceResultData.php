<?php

namespace App\Data\Results;

use App\Models\ResultStatus;
use Spatie\LaravelData\Data;

class RacerRaceResultData extends Data
{
    public function __construct(
      public readonly int $bib,
      public readonly ResultStatus $status,
      public readonly string $name,
      public readonly string $category,
      public readonly string $position, // can be number, DSQ, DNF, DNS
      public readonly string $position_in_category,  // can be number, DSQ, DNF, DNS
      public readonly int $laps,
      public readonly string $total_race_time,
      public readonly string $gap_from_leader, // gap
      public readonly string $gap_from_previous,
      public readonly string $best_lap_time,
      public readonly string $best_lap_number,
      public readonly string $racer_hash,
      public readonly ?float $points = null,

    ) {}
}
