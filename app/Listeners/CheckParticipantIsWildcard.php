<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ParticipantRegistered;

class CheckParticipantIsWildcard
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
    public function handle(ParticipantRegistered $event): void
    {
        // $bonus = $event->race->championship->bonuses()->licenceHash($event->participant->driver_licence)->first();

        // $useBonus = $bonus?->hasRemaining() ?? false;

        if ($bonus && $useBonus) {
            $event->participant->update(['wildcard' => true]);
        }
    }
}
