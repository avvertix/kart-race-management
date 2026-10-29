<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewLicenceRequestPayment
{
    /**
     * Confirm the uploaded payment proof, or reject it so the requester uploads a new one. Rejected proofs are kept.
     *
     * @throws ValidationException when no payment proof awaits confirmation
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, bool $confirm): LicenceRequest
    {
        return DB::transaction(function () use ($licenceRequest, $organizer, $confirm) {
            $locked = $licenceRequest->lockForReview(
                LicenceRequestStatus::underPaymentReview(),
                $organizer,
                __('The payment can be reviewed only while the request is being reviewed.'),
            );

            if ($locked->payment_status !== LicenceRequestPaymentStatus::PaymentSubmitted) {
                throw ValidationException::withMessages(['payment' => __('The request has no payment proof to review.')]);
            }

            $locked->update($confirm
                ? [
                    'payment_status' => LicenceRequestPaymentStatus::Paid,
                    'payment_confirmed_by' => $organizer->getKey(),
                    'paid_at' => now(),
                ]
                : ['payment_status' => LicenceRequestPaymentStatus::AwaitingPayment]);

            return $locked;
        });
    }
}
