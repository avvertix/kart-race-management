<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class LicenceSettingsData extends Data
{
    /**
     * @param  int[]  $accepted_driver_licences  Empty means all licence types accepted
     * @param  int[]  $accepted_competitor_licences  Empty means all licence types accepted
     */
    public function __construct(
        public array $accepted_driver_licences = [],
        public array $accepted_competitor_licences = [],
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
