<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Actions\Enrollments\EnrollPackagedRequest;
use App\Enums\LicenceRequestStatus;
use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Notifications\LicenceRequestReceived;
use App\Notifications\LicenceRequestSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SubmitLicenceRequest
{
    /**
     * Send a complete draft to the organizer. Paying is not required to submit. A request covered by a Package with an
     * Enrollment is enrolled in its championships, see EnrollPackagedRequest.
     *
     * @throws ValidationException when the request is not a draft or something is missing
     */
    public function __invoke(LicenceRequest $licenceRequest): LicenceRequest
    {
        $submitted = DB::transaction(function () use ($licenceRequest) {
            $locked = $licenceRequest->lockInStatus(LicenceRequestStatus::Draft, __('The request was already submitted.'));

            $locked->ensureNothingIsMissing();

            $locked->update([
                'status' => LicenceRequestStatus::Submitted,
                'submitted_at' => now(),
            ]);

            return $locked;
        });

        app(EnrollPackagedRequest::class)($submitted);

        $submitted->user->notify(new LicenceRequestReceived($submitted));

        Notification::send(User::organizersToNotify(NotificationType::LicenceRequestSubmitted), new LicenceRequestSubmitted($submitted));

        return $submitted;
    }
}
