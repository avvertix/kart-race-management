<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Actions\Enrollments\EnrollPackagedRequest;
use App\Enums\LicenceRequestStatus;
use App\Enums\NotificationType;
use App\Models\Enrollment;
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
     * Send a complete draft to the organizer. Paying is not required to submit. It must still fit the championships whose
     * Enrollments wait for it. A request covered by a Package with an Enrollment is enrolled in its championships, see
     * EnrollPackagedRequest.
     *
     * @throws ValidationException when the request is not a draft or something is missing
     */
    public function __invoke(LicenceRequest $licenceRequest): LicenceRequest
    {
        $submitted = DB::transaction(function () use ($licenceRequest) {
            $locked = $licenceRequest->lockInStatus(LicenceRequestStatus::Draft, __('The request was already submitted.'));

            $locked->ensureNothingIsMissing();

            $locked->enrollments()->active()->with('championship')->get()->each(function (Enrollment $enrollment) use ($locked) {
                if (LicenceRequest::query()->whereKey($locked->getKey())->enrollableIn($enrollment->championship)->doesntExist()) {
                    throw ValidationException::withMessages(['request' => __('The request no longer fits :championship, where it is enrolled: check the licence type and the year.', ['championship' => $enrollment->championship->title])]);
                }
            });

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
