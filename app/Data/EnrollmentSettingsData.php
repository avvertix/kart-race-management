<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

/**
 * Whether and when drivers enroll in a championship, see the Enrollment section of docs/reference/championship-settings.md.
 * What they enroll with is the championship's Enrollment Options.
 */
class EnrollmentSettingsData extends Data
{
    public function __construct(
        public bool $enabled = false,
        public ?CarbonImmutable $opens_at = null,
        public ?CarbonImmutable $closes_at = null,
        public bool $requires_competitor_when_underage = true,
    ) {}

    /**
     * Whether drivers can enroll at the given time, now by default.
     */
    public function isOpen(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->enabled
            && $this->opens_at !== null
            && $this->closes_at !== null
            && $at->greaterThanOrEqualTo($this->opens_at)
            && $at->lessThanOrEqualTo($this->closes_at);
    }

    /**
     * Whether the period is still to come at the given time, now by default.
     */
    public function opensLater(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->opens_at !== null && $at->lessThan($this->opens_at);
    }
}
