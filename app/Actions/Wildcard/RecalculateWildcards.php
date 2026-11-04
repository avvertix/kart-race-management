<?php

declare(strict_types=1);

namespace App\Actions\Wildcard;

use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Support\Collection;

class RecalculateWildcards
{
    /**
     * Evaluate again the wildcard status of the participants of a race, all of them or only the given ones, with the
     * championship's wildcard rule, saving the changes unless it is a dry run. Nothing is evaluated when the championship
     * has wildcards off.
     *
     * @param  iterable<int, Participant>|null  $participants  Participants of the race, all of them when null
     * @return Collection<int, array{participant: Participant, current: ?bool, recalculated: bool, changed: bool}>
     */
    public function forRace(Race $race, ?iterable $participants = null, bool $save = true): Collection
    {
        /** @var Championship $championship */
        $championship = $race->getRelationValue('championship');

        if (! $championship->wildcard->enabled) {
            return collect();
        }

        $evaluate = $championship->wildcard->strategy->resolve();

        $participants ??= Participant::query()->where('race_id', $race->getKey())->get();

        $results = collect();

        foreach ($participants as $participant) {
            $current = $participant->wildcard;
            $recalculated = (bool) $evaluate($participant, $race);
            $changed = $current !== $recalculated;

            if ($save && $changed) {
                $participant->wildcard = $recalculated;
                $participant->save();
            }

            $results->push([
                'participant' => $participant,
                'current' => $current,
                'recalculated' => $recalculated,
                'changed' => $changed,
            ]);
        }

        return $results;
    }
}
