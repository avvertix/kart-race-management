<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where the payment of an Enrollment stands: the amount of its Enrollment Option, possibly a deposit first, paid through
 * payment records the organizer confirms.
 */
enum EnrollmentPaymentStatus: string
{
    /**
     * Nothing to pay: a free option, or enrolled by the organizer.
     */
    case NotDue = 'not_due';

    case AwaitingPayment = 'awaiting_payment';

    case PaymentSubmitted = 'payment_submitted';

    /**
     * The deposit is paid: the Enrollment gets what its option includes, the balance is still due.
     */
    case DepositPaid = 'deposit_paid';

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
