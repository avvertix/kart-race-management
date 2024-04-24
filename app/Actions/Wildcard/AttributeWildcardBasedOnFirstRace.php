<?php

namespace App\Actions\Wildcard;

use App\Models\Race;
use App\Models\Participant;

class AttributeWildcardBasedOnFirstRace
{
    /**
     * Identify if a participant should have the wildcard status in a race within a championship
     *
     * @param  \App\Models\Participant  $participant
     * @param  \App\Models\Race  $race
     * @return bool|null the wildcard status
     */
    public function __invoke(Participant $participant, Race $race): ?bool
    {
        $firstRace = $race->championship->races()->closed()->first();

        if(is_null($firstRace)){
            return false;
        }

        if($firstRace->is($race)){
            return false;
        }

        return true;
    }

    
    
}
