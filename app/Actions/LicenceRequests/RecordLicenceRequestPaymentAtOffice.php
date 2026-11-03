<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\PaymentChannelType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordLicenceRequestPaymentAtOffice
{
    /**
     * Record that the requester paid at the organizer's office, by cash or credit card, instead of a bank transfer.
     *
     * @throws ValidationException when the channel is a bank transfer, or the request is not awaiting a payment
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, PaymentChannelType $channel): LicenceRequest
    {
        if (! in_array($channel, [PaymentChannelType::CASH, PaymentChannelType::CREDIT_CARD], true)) {
            throw ValidationException::withMessages(['payment' => __('At the office the payment is by cash or credit card.')]);
        }

        return DB::transaction(function () use ($licenceRequest, $organizer, $channel) {
            $locked = $licenceRequest->lockForReview(
                LicenceRequestStatus::verified(),
                $organizer,
                __('The payment can be recorded only once the request is verified.'),
            );

            if ($locked->payment_status !== LicenceRequestPaymentStatus::AwaitingPayment) {
                throw ValidationException::withMessages(['payment' => $locked->payment_status === LicenceRequestPaymentStatus::PaymentSubmitted
                    ? __('A payment proof awaits your review. Confirm or reject it first.')
                    : __('The request is not awaiting a payment.')]);
            }

            $locked->update([
                'payment_status' => LicenceRequestPaymentStatus::Paid,
                'payment_channel' => $channel,
                'payment_confirmed_by' => $organizer->getKey(),
                'paid_at' => now(),
            ]);

            return $locked;
        });
    }
}
