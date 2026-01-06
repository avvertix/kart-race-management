<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ParticipantRegistered;
use Closure;

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
    public function handle(ParticipantRegistered $event, Closure $next): ParticipantRegistered
    {
        // TODO: apply bonus only if is not national race or above

        $championship = $event->race->championship;

        $fixedBonusAmount = $championship->bonuses->fixed_bonus_amount ?? (int) config('races.bonus_amount');

        $registrationPrice = $championship->registration_price ?? (int) config('races.price');

        $bonus = $championship->bonuses()->licenceHash($event->participant->driver_licence)->first();

        // Check if bonus already applied to participant
        if ($event->participant->bonuses()->exists()) {
            return $next($event);
        }

        if (blank($bonus)) {
            return $next($event);
        }

        if (! $bonus->hasRemaining()) {
            return $next($event);
        }

        $remainingBonuses = $bonus->remaining();

        $event->participant->update(['use_bonus' => true]);

        if ((bool) config('races.bonus_use_one_at_time')) {
            $event->participant->bonuses()->attach($bonus);

            return $next($event);
        }

        // Calculate how many bonuses can be applied based on the registration price
        $bonusCount = intdiv($registrationPrice, $fixedBonusAmount);

        // Ensure we do not exceed the remaining bonuses
        $bonusCount = min($bonusCount, $remainingBonuses);

        // Attach the bonuses to the participant
        for ($i = 0; $i < $bonusCount; $i++) {
            $event->participant->bonuses()->attach($bonus);
        }

        return $next($event);
    }
}
