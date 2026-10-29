<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ForwardLicenceRequest
{
    /**
     * Record that the request was sent to the Licence Provider. It must be paid, or covered by a package, and have no open flags.
     *
     * @throws ValidationException when the request is not being reviewed, not paid or still has flags
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer): LicenceRequest
    {
        return DB::transaction(function () use ($licenceRequest, $organizer) {
            $locked = $licenceRequest->lockForReview(
                LicenceRequestStatus::underReview(),
                $organizer,
                __('Only a request being reviewed can be forwarded.'),
            );

            if (! in_array($locked->payment_status, [LicenceRequestPaymentStatus::Paid, LicenceRequestPaymentStatus::Covered], true)) {
                throw ValidationException::withMessages(['request' => __('Confirm the payment before forwarding the request.')]);
            }

            if (! $locked->hasResolvedAllChanges()) {
                throw ValidationException::withMessages(['request' => __('Remove the flags or ask for changes before forwarding the request.')]);
            }

            $locked->update([
                'status' => LicenceRequestStatus::Forwarded,
                'forwarded_at' => now(),
            ]);

            return $locked;
        });
    }
}
