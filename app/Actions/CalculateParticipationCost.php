<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
use Closure;
use Illuminate\Validation\ValidationException;

class CalculateParticipationCost
{
    /**
     * Calculate and save participation cost for the current participant.
     */
    public function handle(ParticipantRegistered|ParticipantUpdated $event, Closure $next): ParticipantRegistered|ParticipantUpdated
    {
        if ($event->race->isCancelled()) {
            throw ValidationException::withMessages([
                'bib' => __('The race has been cancelled and registration is now closed.'),
            ]);
        }

        // TODO: Implement actual cost calculation logic here

        return $next($event);
    }
}
