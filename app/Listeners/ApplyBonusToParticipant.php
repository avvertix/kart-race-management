<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ParticipantRegistered;

class ApplyBonusToParticipant
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
        $bonus = $event->race->championship->bonuses()->licenceHash($event->participant->driver_licence)->first();

        $useBonus = $bonus?->hasRemaining() ?? false; // todo: verify that participant has not already used a bonus for the race

        if ($bonus && $useBonus) {
            $event->participant->update(['use_bonus' => true]);
            $event->participant->bonuses()->attach($bonus);
        }
    }
}
