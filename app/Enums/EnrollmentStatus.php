<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where an Enrollment is in its lifecycle, see docs/reference/lifecycles.md#enrollment.
 */
enum EnrollmentStatus: string
{
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /**
     * The statuses in which an Enrollment counts for its Licence: a Licence has at most one per championship.
     *
     * @return array<int, self>
     */
    public static function active(): array
    {
        return [self::Submitted, self::Confirmed];
    }

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.enrollment_status.{$this->value}");
    }

    /**
     * Whether the Enrollment counts for its Licence, see active().
     */
    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }
}
