<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\LicenceProvider;
use Spatie\LaravelData\Data;

class LicenceSettingsData extends Data
{
    /**
     * @param  int[]  $accepted_driver_licences  Empty means all licence types accepted
     * @param  int[]  $accepted_competitor_licences  Empty means all licence types accepted
     * @param  ?LicenceProvider  $provider  Who issues the licences; none means the platform-wide provider in races.licence.provider
     */
    public function __construct(
        public array $accepted_driver_licences = [],
        public array $accepted_competitor_licences = [],
        public ?LicenceProvider $provider = null,
    ) {}

    public function hasDriverLicenceRestriction(): bool
    {
        return ! empty($this->accepted_driver_licences);
    }

    public function hasCompetitorLicenceRestriction(): bool
    {
        return ! empty($this->accepted_competitor_licences);
    }
}
