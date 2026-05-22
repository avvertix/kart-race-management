<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ItalianRegion;
use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
use App\Models\ItalianPostalCode;
use Closure;

class DetermineParticipantZone
{
    /**
     * Detect the participant's region from their residence province (then CAP as fallback),
     * and when the race has zone regions configured, auto-set the out-of-zone status.
     * When the zone is configured but the region cannot be determined, defaults to out-of-zone.
     */
    public function handle(ParticipantRegistered|ParticipantUpdated $event, Closure $next): ParticipantRegistered|ParticipantUpdated
    {
        if ($event->race->isCancelled()) {
            return $next($event);
        }

        if (! $event->race->isNationalOrInternational()) {
            return $next($event);
        }

        $participant = $event->participant;
        $race = $event->race;

        $address = $participant->driver['residence_address'] ?? [];
        $province = $address['province'] ?? null;
        $postalCode = $address['postal_code'] ?? null;

        $region = blank($province) ? null : ItalianRegion::fromProvince($province);

        if ($region === null && ! blank($postalCode)) {
            $region = ItalianPostalCode::findRegionByCap(trim($postalCode));
        }

        $participant->region = $region;

        if ($race->hasZoneConfigured()) {
            $properties = $participant->properties;
            $properties['out_of_zone'] = $region === null
                || ! in_array($region->value, $race->zone_regions?->toArray() ?? [], true);
            $participant->properties = $properties;
        }

        $participant->save();

        return $next($event);
    }
}
