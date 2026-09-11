<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class RegistrationSettingsData extends Data
{
    public function __construct(
        /**
         * Whether to allow different BIBs for the same driver along the championship. If false, a driver must use the same BIB across all races of the championship
         */
        public bool $allow_different_bibs = false,

        /**
         * Driver licence numbers that always register with $shared_bib in this championship,
         * regardless of the BIB submitted at registration or the BIB used in previous races.
         * Drivers listed here are exempt from the championship-wide rule that a BIB can only
         * belong to a single driver, but not from the rule that a BIB must be unique within a
         * single race.
         *
         * @var list<string>
         */
        public array $shared_bib_licences = [],

        /**
         * The BIB shared by the driver licences listed in $shared_bib_licences.
         */
        public ?int $shared_bib = null,
    ) {}
}
