<?php

declare(strict_types=1);

namespace App\Actions\Wildcard;

use App\Enums\EnrollmentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Participant;
use App\Models\Race;
use App\Models\WildcardStrategy;
use Illuminate\Database\Eloquent\Builder;

class AttributeWildcardBasedOnEnrollment
{
    /**
     * Identify if a participant should have the wildcard status in a race within a championship: only participants whose
     * licence number matches the Licence of a confirmed Enrollment in the championship score points.
     *
     * @return bool the wildcard status. True if participant is wildcard.
     */
    public function __invoke(Participant $participant, Race $race): bool
    {
        /** @var Championship $championship loaded once per race, as the sibling strategies do */
        $championship = $race->getRelationValue('championship');

        if (! $championship->wildcard->enabled || $championship->wildcard->strategy !== WildcardStrategy::BASED_ON_ENROLLMENT) {
            return false;
        }

        if (! $participant->race()->is($race)) {
            return false;
        }

        $isEnrolled = Enrollment::query()
            ->where('championship_id', $championship->getKey())
            ->where('status', EnrollmentStatus::Confirmed)
            ->whereHas('licence', fn (Builder $query) => $query->where('number_hash', $participant->driver_licence))
            ->exists();

        return ! $isEnrolled;
    }
}
