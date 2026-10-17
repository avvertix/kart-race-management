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
         * Driver licence numbers that register with $shared_bib in this championship, regardless
         * of the BIB submitted at registration or the BIB used in previous races. While using
         * $shared_bib they are exempt from BIB uniqueness (within the race and the championship),
         * previous-race consistency and BIB reservation checks. An organizer can later assign a
         * different BIB, which is then validated with the standard rules.
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
