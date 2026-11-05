<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where the payment of an Enrollment stands: the amount of its Enrollment Option, paid by bank transfer with a proof
 * the organizer confirms.
 */
enum EnrollmentPaymentStatus: string
{
    /**
     * Nothing to pay: a free option, or enrolled by the organizer.
     */
    case NotDue = 'not_due';

    case AwaitingPayment = 'awaiting_payment';

    case PaymentSubmitted = 'payment_submitted';

    case Paid = 'paid';

    /**
     * Whether nothing is left to pay.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::NotDue, self::Paid], true);
    }

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.enrollment_payment_status.{$this->value}");
    }
}
