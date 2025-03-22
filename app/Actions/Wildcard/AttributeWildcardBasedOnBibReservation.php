<?php

declare(strict_types=1);

namespace App\Actions\Wildcard;

use App\Models\Participant;
use App\Models\Race;
use App\Models\WildcardStrategy;

class AttributeWildcardBasedOnBibReservation
{
    /**
     * Identify if a participant should have the wildcard status in a race within a championship
     *
     * @return bool the wildcard status
     */
    public function __invoke(Participant $participant, Race $race): bool
    {
        $championship = $race->championship;

        if (! $championship->wildcard->enabled || $championship->wildcard->strategy !== WildcardStrategy::BASED_ON_BIB_RESERVATION) {
            return false;
        }

        if (! $participant->race()->is($race)) {
            return false;
        }

        return $championship->reservations()->licenceHash($participant->driver_licence)->exists() === false;
    }
}
