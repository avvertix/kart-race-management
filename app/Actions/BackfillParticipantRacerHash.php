<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Participant;

class BackfillParticipantRacerHash
{
    /**
     * Backfill racer_hash for all participants.
     * Racer hash is the first 8 characters of the driver_licence hash.
     *
     * @return void
     */
    public function __invoke(): void
    {
        Participant::whereNull('racer_hash')
            ->orWhere('racer_hash', '')
            ->chunk(100, function ($participants){
                foreach ($participants as $participant) {
                    $racerHash = mb_substr($participant->driver_licence, 0, 8);

                    $participant->update(['racer_hash' => $racerHash]);
                }
            });
    }
}
