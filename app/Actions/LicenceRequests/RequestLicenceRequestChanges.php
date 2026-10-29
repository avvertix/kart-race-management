<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestLicenceRequestChanges
{
    /**
     * Send the flagged sections and documents to the requester, who fixes them and submits again.
     *
     * @throws ValidationException when the request is not being reviewed or nothing is flagged
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer): LicenceRequest
    {
        return DB::transaction(function () use ($licenceRequest, $organizer) {
            $locked = $licenceRequest->lockForReview(
                LicenceRequestStatus::underReview(),
                $organizer,
                __('Changes can be requested only while the request waits for the organizer.'),
            );

            if ($locked->hasResolvedAllChanges()) {
                throw ValidationException::withMessages(['request' => __('Flag at least one section or document first.')]);
            }

            $locked->update(['status' => LicenceRequestStatus::ChangesRequested]);

            return $locked;
        });
    }
}
