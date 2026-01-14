<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
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
    public function handle(ParticipantRegistered|ParticipantUpdated $event, Closure $next): ParticipantRegistered|ParticipantUpdated
    {
        if ($event->race->isNationalOrInternational()) {
            return $next($event);
        }

        $championship = $event->race->championship;

        $fixedBonusAmount = $championship->bonuses->fixed_bonus_amount ?? (int) config('races.bonus_amount');

        $registrationPrice = $event->participant->racingCategory->registration_price
            ?? $championship->registration_price
            ?? (int) config('races.price');

        $bonus = $championship->bonuses()->licenceHash($event->participant->driver_licence)->first();

        // Check if bonus already applied to participant
        if ($event->participant->bonuses()->exists() || $event->participant->use_bonus) {
            return $next($event);
        }

        if (blank($bonus)) {
            return $next($event);
        }

        if (! $bonus->hasRemaining()) {
            return $next($event);
        }

        $bonusMode = $championship->bonuses->bonus_mode ?? \App\Models\BonusMode::CREDIT;

        $remainingBonuses = $bonus->remaining;

        $event->participant->update(['use_bonus' => true]);

        if ($bonusMode === \App\Models\BonusMode::CREDIT) {
            // In CREDIT mode, deduct the actual registration cost from the credit balance
            // The bonus amount represents total credit available

            // BALANCE mode: traditional behavior with discrete bonus units
            if ((bool) config('races.bonus_use_one_at_time')) {
                $event->participant->bonuses()->attach($bonus, ['amount' => $fixedBonusAmount]);

                return $next($event);
            }

            // Calculate how many bonuses can be applied based on the registration price
            $bonusCount = intdiv($registrationPrice, $fixedBonusAmount);

            // Ensure we do not exceed the remaining bonuses
            $bonusCount = min($bonusCount, $remainingBonuses);

            // Attach the bonuses to the participant
            for ($i = 0; $i < $bonusCount; $i++) {
                $event->participant->bonuses()->attach($bonus, ['amount' => $fixedBonusAmount]);
            }

            return $next($event);
        }

        $amountToDeduct = min($remainingBonuses, $registrationPrice);

        if ($amountToDeduct > 0) {
            $event->participant->bonuses()->attach($bonus, ['amount' => $amountToDeduct]);
        }

        return $next($event);
    }
}
