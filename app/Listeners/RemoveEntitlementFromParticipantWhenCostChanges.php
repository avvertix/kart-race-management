<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\ReleaseEntitlements;
use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
use App\Models\ActivityLogEntry;
use Closure;
use Spatie\Activitylog\Models\Activity;

class RemoveEntitlementFromParticipantWhenCostChanges
{
    public function __construct(private ReleaseEntitlements $releaseEntitlements) {}

    /**
     * Pipeline stage: when the category (and so the race fee) or the licence number changed before any payment, give the
     * Entitlement usage back, so that ApplyEntitlementToParticipant applies again the one of the right licence on the new
     * fee.
     */
    public function handle(ParticipantRegistered|ParticipantUpdated $event, Closure $next): ParticipantRegistered|ParticipantUpdated
    {
        $participant = $event->participant;

        if ($event->race->isCancelled()
            || filled($participant->payment_confirmed_at)
            || $participant->payments()->exists()) {
            return $next($event);
        }

        $feeOrLicenceChanged = $participant->activitiesAsSubject()
            ->forEvent('updated')
            ->get()
            ->contains(fn (Activity $activity) => $activity instanceof ActivityLogEntry
                && array_intersect_key($activity->changedAttributes(), array_flip(['category_id', 'driver_licence'])) !== []);

        if ($feeOrLicenceChanged) {
            ($this->releaseEntitlements)($participant);
        }

        return $next($event);
    }
}
