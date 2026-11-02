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

class VerifyLicenceRequest
{
    /**
     * Finish the review without changes to ask: every document viewed and no comment left. The amount, in cents, is what the
     * requester pays next; a request covered by a package needs none.
     *
     * @throws ValidationException when the request is not being reviewed, a document is not viewed, a comment is left or the amount is missing
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, ?int $amount): LicenceRequest
    {
        return DB::transaction(function () use ($licenceRequest, $organizer, $amount) {
            $locked = $licenceRequest->lockForReview(
                LicenceRequestStatus::underReview(),
                $organizer,
                __('Only a request being reviewed can be verified.'),
            );

            $isCovered = $locked->payment_status === LicenceRequestPaymentStatus::Covered;

            if (! $isCovered) {
                Validator::make(['amount' => $amount], ['amount' => 'required|integer|min:0'])->validate();

                if (! $locked->canBePriced()) {
                    throw ValidationException::withMessages(['amount' => __('The amount cannot change after a payment proof is uploaded.')]);
                }
            }

            if (! $locked->hasResolvedAllChanges()) {
                throw ValidationException::withMessages(['request' => __('Remove the comments or request changes.')]);
            }

            if (! $locked->isEveryDocumentViewed()) {
                throw ValidationException::withMessages(['request' => __('Mark every document as viewed first.')]);
            }

            $locked->update([
                'status' => LicenceRequestStatus::Verified,
                'verified_at' => now(),
                ...($isCovered ? [] : [
                    'payment_status' => LicenceRequestPaymentStatus::AwaitingPayment,
                    'amount' => $amount,
                    'priced_by' => $organizer->getKey(),
                    'priced_at' => now(),
                ]),
            ]);

            return $locked;
        });
    }
}
