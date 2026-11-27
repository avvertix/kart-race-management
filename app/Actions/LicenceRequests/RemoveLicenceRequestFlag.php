<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequestChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveLicenceRequestFlag
{
    /**
     * Remove a section flag not sent to the requester yet.
     *
     * @throws ValidationException when the flag was already sent or fixed
     */
    public function __invoke(LicenceRequestChangeRequest $changeRequest, User $organizer): void
    {
        DB::transaction(function () use ($changeRequest, $organizer) {
            $changeRequest->licenceRequest->lockForReview(
                LicenceRequestStatus::underReview(),
                $organizer,
                __('The flag was already sent to the requester.'),
            );

            $changeRequest = $changeRequest->fresh();

            if ($changeRequest === null) {
                return;
            }

            if ($changeRequest->resolved_at !== null) {
                throw ValidationException::withMessages(['request' => __('The flag was already fixed by the requester.')]);
            }

            $changeRequest->delete();
        });
    }
}
