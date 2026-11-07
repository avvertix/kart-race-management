<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Race;
use Illuminate\Support\Collection;

/**
 * Same MyLaps Orbits format of the participants export, limited to the given drivers
 * and sorted as given (a starting grid: category, then starting position).
 */
class RaceGridForTimingExport extends RaceParticipantsForTimingExport
{
    /**
     * @param  Collection<int, int>  $bibs  Bibs in starting grid order
     */
    public function __construct(
        Race $race,
        private Collection $bibs,
        ?Collection $mappings = null,
    ) {
        parent::__construct($race, $mappings);
    }

    public function query()
    {
        $query = $this->race
            ->participants()
            ->has('transponders')
            ->whereIn('bib', $this->bibs->all());

        if ($this->bibs->isNotEmpty()) {
            $query->orderByRaw('FIELD(bib, '.$this->bibs->map(fn ($bib) => (int) $bib)->implode(',').')');
        }

        return $query;
    }
}
