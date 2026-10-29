<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where the payment of a Licence Request stands. Requests are paid on their own, not through an Order.
 */
enum LicenceRequestPaymentStatus: string
{
    /**
     * The organizer sets the amount after checking the request.
     */
    case AwaitingPrice = 'awaiting_price';

    case AwaitingPayment = 'awaiting_payment';

    case PaymentSubmitted = 'payment_submitted';

    case Paid = 'paid';

    /**
     * Paid with a Promotional Package that includes the renewal.
     */
    case Covered = 'covered';

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.request_payment_status.{$this->value}");
    }
}
