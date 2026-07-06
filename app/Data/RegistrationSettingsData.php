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
    ) {}
}
