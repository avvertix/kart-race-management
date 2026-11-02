<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetLicenceRequestAmount
{
    /**
     * Change the amount to pay, in cents, set when verifying the request. It can change until a payment proof is uploaded.
     *
     * @throws ValidationException when the request is not verified, covered by a package or a proof was already uploaded
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, int $amount): LicenceRequest
    {
        Validator::make(['amount' => $amount], ['amount' => 'required|integer|min:0'])->validate();

        return DB::transaction(function () use ($licenceRequest, $organizer, $amount) {
            $locked = $licenceRequest->lockForReview(
                [LicenceRequestStatus::Verified],
                $organizer,
                __('The amount can change only once the request is verified.'),
            );

            if (! $locked->canBePriced()) {
                throw ValidationException::withMessages(['amount' => __('The amount cannot change after a payment proof is uploaded.')]);
            }

            $locked->update([
                'payment_status' => LicenceRequestPaymentStatus::AwaitingPayment,
                'amount' => $amount,
                'priced_by' => $organizer->getKey(),
                'priced_at' => now(),
            ]);

            return $locked;
        });
    }
}
