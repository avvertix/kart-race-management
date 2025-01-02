<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;

class GenerateRaceNumber
{
    private const HIGHEST_SUGGESTED_RACE_NUMBER = 999;

    /**
     * Generate a set of race numbers based on available ones within the championship
     *
     * @param  int  $count  How many suggestions to generate
     * @return array|\Illuminate\Support\Collection
     */
    public function __invoke(Championship $championship, $count = 4)
    {
        $count = abs($count);

        $existing = Participant::where('championship_id', $championship->getKey())->distinct()->select('bib')->pluck('bib');

        $reserved = BibReservation::where('championship_id', $championship->getKey())->distinct()->select('bib')->pluck('bib');

        $max = max($existing->max(), self::HIGHEST_SUGGESTED_RACE_NUMBER);

        $options = collect()->range(1, $max === 0 ? 100 : $max)->diff($existing)->diff($reserved);

        $range = $options->count() > $count ? $options->take($count) : $options;

        return $range->values()->toArray();
    }
}
