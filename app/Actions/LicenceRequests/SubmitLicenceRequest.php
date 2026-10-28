<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitLicenceRequest
{
    /**
     * Send a complete draft to the organizer. Paying is not required to submit.
     *
     * @throws ValidationException when the request is not a draft or something is missing
     */
    public function __invoke(LicenceRequest $licenceRequest): LicenceRequest
    {
        return DB::transaction(function () use ($licenceRequest) {
            $locked = $licenceRequest->lockInStatus(LicenceRequestStatus::Draft, __('The request was already submitted.'));

            $locked->ensureNothingIsMissing();

            $locked->update([
                'status' => LicenceRequestStatus::Submitted,
                'submitted_at' => now(),
            ]);

            return $locked;
        });
    }
}
