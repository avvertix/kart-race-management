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
        $championship = $event->race->championship;

        $fixedBonusAmount = $championship->bonuses->fixed_bonus_amount ?? (int) config('races.bonus_amount'); // TODO: read from config if not set

        $registrationPrice = $championship->registration_price ?? (int) config('races.price'); // TODO: read from config if not set

        $bonus = $championship->bonuses()->licenceHash($event->participant->driver_licence)->first();

        if (blank($bonus)) {
            return;
        }

        if (! $bonus->hasRemaining()) {
            return;
        }

        $remainingBonuses = $bonus->remaining();

        $event->participant->update(['use_bonus' => true]);

        // $event->participant->bonuses()->attach($bonus);

        // Calculate how many bonuses can be applied based on the registration price
        $bonusCount = intdiv($registrationPrice, $fixedBonusAmount);

        // Ensure we do not exceed the remaining bonuses
        $bonusCount = min($bonusCount, $remainingBonuses);

        // Attach the bonuses to the participant
        for ($i = 0; $i < $bonusCount; $i++) {
            $event->participant->bonuses()->attach($bonus);
        }

    }
}
