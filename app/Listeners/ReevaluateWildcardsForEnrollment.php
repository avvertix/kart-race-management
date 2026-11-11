<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Wildcard\RecalculateWildcards;
use App\Events\EnrollmentConfirmed;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\WildcardStrategy;
use Illuminate\Support\Collection;

class ReevaluateWildcardsForEnrollment
{
    public function __construct(private RecalculateWildcards $recalculateWildcards) {}

    /**
     * When an Enrollment is confirmed in a championship with wildcards based on enrollment, evaluate again
     * the driver's participations in races without results. Races with results keep their status until the organizer
     * recalculates them.
     */
    public function handle(EnrollmentConfirmed $event): void
    {
        $enrollment = $event->enrollment;
        $championship = $enrollment->championship;
        $licence = $enrollment->licence;

        if ($licence === null
            || ! $championship->wildcard->enabled
            || $championship->wildcard->strategy !== WildcardStrategy::BASED_ON_ENROLLMENT) {
            return;
        }

        Participant::query()
            ->where('championship_id', $championship->getKey())
            ->where('driver_licence', $licence->number_hash)
            ->whereNotIn('race_id', RunResult::query()->select('race_id'))
            ->get()
            ->groupBy('race_id')
            ->each(fn (Collection $participants, int|string $raceId) => $this->recalculateWildcards->forRace(Race::query()->findOrFail($raceId), $participants));
    }
}
