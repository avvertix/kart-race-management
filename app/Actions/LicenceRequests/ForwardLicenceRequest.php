<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ForwardLicenceRequest
{
    /**
     * Record that the verified request was handed to the Licence Provider, which emits the licence later. It is optional:
     * a licence the provider emits at once is emitted from the verified request. It must be paid, or covered by a package.
     *
     * @throws ValidationException when the request is not verified or not paid
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer): LicenceRequest
    {
        return DB::transaction(function () use ($licenceRequest, $organizer) {
            $locked = $licenceRequest->lockForReview(
                [LicenceRequestStatus::Verified],
                $organizer,
                __('Only a verified request can be handed to the provider.'),
            );

            if (! $locked->isPaymentSettled()) {
                throw ValidationException::withMessages(['request' => __('Confirm the payment before handing the request to the provider.')]);
            }

            $locked->update([
                'status' => LicenceRequestStatus::Forwarded,
                'forwarded_at' => now(),
            ]);

            return $locked;
        });
    }
}
