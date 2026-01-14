<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
use Closure;

class RemoveBonusFromParticipantWhenCostChanges
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ParticipantRegistered|ParticipantUpdated $event, Closure $next): ParticipantRegistered|ParticipantUpdated
    {
        if ($event->race->isNationalOrInternational()) {
            return $next($event);
        }

        // If the registration cost has changed, we need to remove the bonus usage before it gets calculated again

        if($event->race->isCancelled()){
            return $next($event);
        }

        // TODO: what if the race is already completed?

        // Check if category_id changed in any of the updated activities
        $activities = $event->participant->activities()
            ->forEvent('updated')
            ->get();

        $categoryChanged = $activities->contains(function ($activity) {
            $changes = $activity->changes();
            return isset($changes['attributes']['category_id']);
        });

        if ($categoryChanged) {
            $event->participant->bonuses()->detach();
            $event->participant->update(['use_bonus' => false]);
        }

        return $next($event);
    }
}
