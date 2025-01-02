<?php

declare(strict_types=1);

namespace App\Actions\Wildcard;

use App\Models\Participant;
use App\Models\Race;

class AttributeWildcardBasedOnFirstRace
{
    /**
     * Identify if a participant should have the wildcard status in a race within a championship
     *
     * @return bool|null the wildcard status
     */
    public function __invoke(Participant $participant, Race $race): ?bool
    {
        $firstRace = $race->championship->races()->closed()->first();

        if (is_null($firstRace)) {
            return false;
        }

        if ($firstRace->is($race)) {
            return false;
        }

        return true;
    }
}
