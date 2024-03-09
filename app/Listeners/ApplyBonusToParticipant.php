<?php

namespace App\Listeners;

use App\Events\ParticipantRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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

        $useBonus = $bonus?->hasRemaining() ?? false;

        if($bonus && $useBonus){
            $event->participant->update(['use_bonus' => true]);
            $event->participant->bonuses()->attach($bonus);
        }
    }
}
