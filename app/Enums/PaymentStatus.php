<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a payment record of an Enrollment or a Licence Request stands. Race participant payments have none.
 */
enum PaymentStatus: string
{
    /**
     * A proof uploaded by the driver, waiting for the organizer.
     */
    case Submitted = 'submitted';

    /**
     * The organizer confirmed the amount received, or recorded it.
     */
    case Confirmed = 'confirmed';

    /**
     * The organizer could not match the proof to a payment received; it is kept.
     */
    case Rejected = 'rejected';

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.payment_status.{$this->value}");
    }
}
