<?php

declare(strict_types=1);

namespace App\Actions\Wildcard;

use App\Models\Participant;
use App\Models\Race;
use App\Models\WildcardStrategy;

class AttributeWildcardBasedOnBonus
{
    /**
     * Identify if a participant should have the wildcard status in a race within a championship
     *
     * @return bool|null the wildcard status
     */
    public function __invoke(Participant $participant, Race $race): ?bool
    {
        $championship = $race->championship;

        if (! $championship->wildcard->enabled || $championship->wildcard->strategy !== WildcardStrategy::BASED_ON_BONUS) {
            return false;
        }

        if (! $participant->race()->is($race)) {
            return false;
        }

        $bonus = $championship->bonuses()->licenceHash($participant->driver_licence)->first();

        if (blank($bonus)) {
            return true;
        }

        return $bonus->amount < ($championship->wildcard->requiredBonusAmount ?? 1);
    }
}
