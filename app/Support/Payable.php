<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

/**
 * Something paid by bank transfer with a proof the organizer confirms: an Enrollment, a Licence Request or an Order.
 */
interface Payable
{
    /**
     * The payment proofs uploaded, rejected ones included.
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
     * Record that a proof was uploaded and waits for the organizer.
     */
    public function markPaymentSubmitted(): void;
}
