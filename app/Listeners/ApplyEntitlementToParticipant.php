<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EntitlementMode;
use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
use App\Models\Entitlement;
use Closure;
use Illuminate\Support\Facades\DB;

class ApplyEntitlementToParticipant
{
    /**
     * Pipeline stage: discount the race fee with one Entitlement of the participant's licence, when the race accepts
     * them. The oldest one is used, credits before balance: a credit covers the full race fee of the chosen category, a
     * balance up to what is left. Tyres are never discounted. Nothing changes once a payment is recorded.
     */
    public function handle(ParticipantRegistered|ParticipantUpdated $event, Closure $next): ParticipantRegistered|ParticipantUpdated
    {
        $participant = $event->participant;
        $race = $event->race;

        if (! $race->isEntitlementEnabled() || $race->isCancelled()) {
            return $next($event);
        }

        if (filled($participant->payment_confirmed_at) || $participant->payments()->exists()) {
            return $next($event);
        }

        $raceFee = $participant->racingCategory->registration_price
            ?? $race->championship->registration_price
            ?? (int) config('races.price');

        DB::transaction(function () use ($participant, $race, $raceFee) {
            $participant->newQuery()->whereKey($participant->getKey())->lockForUpdate()->first();

            if ($participant->entitlements()->exists()) {
                return;
            }

            $entitlement = Entitlement::query()
                ->where('championship_id', $race->championship_id)
                ->where('licence_hash', $participant->driver_licence)
                ->available()
                ->orderByRaw('CASE WHEN mode = ? THEN 0 ELSE 1 END', [EntitlementMode::Credit->value])
                ->oldest()
                ->oldest('id')
                ->lockForUpdate()
                ->first();

            if ($entitlement === null || $raceFee <= 0) {
                return;
            }

            $participant->entitlements()->attach($entitlement, ['amount' => $entitlement->consume($raceFee)]);
        });

        return $next($event);
    }
}
