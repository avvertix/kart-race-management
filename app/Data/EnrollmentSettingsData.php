<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

/**
 * Whether, when and how drivers enroll in a championship, see the Enrollment section of docs/reference/championship-settings.md.
 */
class EnrollmentSettingsData extends Data
{
    /**
     * @param  ?int  $product_id  The enrollment Product sold for a fixed fee; none means enrolling is free
     * @param  bool  $fee_covers_licence_renewal  Whether the fee also pays the Licence Request an Enrollment waits for
     */
    public function __construct(
        public bool $enabled = false,
        public ?CarbonImmutable $opens_at = null,
        public ?CarbonImmutable $closes_at = null,
        public ?int $product_id = null,
        public bool $grants_bib_reservation = false,
        public bool $requires_competitor_when_underage = true,
        public bool $fee_covers_licence_renewal = false,
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

    /**
     * Whether enrolling is free, as no Product is sold for it.
     */
    public function isFree(): bool
    {
        return $this->product_id === null;
    }
}
