<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RejectLicenceRequest
{
    /**
     * Close the request without a licence, with a reason shown to the requester. Refunds are settled by email.
     *
     * @throws ValidationException when the reason is missing or the request cannot be rejected
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, string $reason): LicenceRequest
    {
        Validator::make(['rejection_reason' => $reason], ['rejection_reason' => 'required|string|max:1000'])->validate();

        return DB::transaction(function () use ($licenceRequest, $organizer, $reason) {
            $locked = $licenceRequest->lockForReview(
                LicenceRequestStatus::rejectable(),
                $organizer,
                __('Only a submitted, in review or forwarded request can be rejected.'),
            );

            $locked->update([
                'status' => LicenceRequestStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $locked;
        });
    }
}
