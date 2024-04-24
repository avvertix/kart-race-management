<?php

namespace App\Listeners;

use App\Events\ParticipantRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckParticipantForWildcard implements ShouldQueue
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
        $championship = $event->race->championship;
        
        if(!$championship->wildcard?->enabled){
            return ;
        }

        $evaluate = $championship->wildcard->strategy->resolve();

        $event->participant->wildcard = $evaluate($event->participant, $event->race);
        $event->participant->save();
    }
}
