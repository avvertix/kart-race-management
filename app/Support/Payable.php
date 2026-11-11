<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

/**
 * Something paid through payment records the organizer confirms: bank transfer proofs uploaded by the driver, or payments
 * the organizer records. An Enrollment or a Licence Request.
 */
interface Payable
{
    /**
     * The payment records: proofs uploaded, rejected ones included, and payments recorded by the organizer.
     *
     * @return MorphMany<Payment, covariant Model>
     */
    public function payments(): MorphMany;

    /**
     * Lock the record for the rest of the current transaction, ensuring it still waits for a payment.
     *
     * @throws ValidationException under the given key, saying why no payment is expected
     */
    public function lockAwaitingPayment(string $errorKey): self;

    /**
     * Update the payment status once a proof is uploaded and waits for the organizer.
     */
    public function markPaymentSubmitted(): void;
}
