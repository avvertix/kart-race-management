<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Notifications\LicenceRequestSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ResubmitLicenceRequest
{
    /**
     * Send the fixes back to the organizer, once every change request is resolved and no document is still flagged.
     *
     * @throws ValidationException when no changes were requested or something is still to fix
     */
    public function __invoke(LicenceRequest $licenceRequest): LicenceRequest
    {
        $resubmitted = DB::transaction(function () use ($licenceRequest) {
            $locked = $licenceRequest->lockInStatus(LicenceRequestStatus::ChangesRequested, __('The organizer did not ask for changes.'));

            if (! $locked->hasResolvedAllChanges()) {
                throw ValidationException::withMessages([
                    'request' => __('Fix every flagged section and document before sending the request back.'),
                ]);
            }

            $locked->ensureNothingIsMissing();

            $locked->update(['status' => LicenceRequestStatus::Submitted]);

            return $locked;
        });

        Notification::send(User::organizersToNotify(NotificationType::LicenceRequestSubmitted), new LicenceRequestSubmitted($resubmitted, isResubmission: true));

        return $resubmitted;
    }
}
