<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Models\Enrollment;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Notifications\EnrollmentNeedsAction;
use App\Notifications\LicenceRequestRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RejectLicenceRequest
{
    /**
     * Close the request without a licence, with a reason shown to the requester. Refunds are settled by email. The
     * Enrollments waiting for the request stay submitted: the User picks another Licence for them, and the Order that
     * covered the request covers a new one. Besides the rejection, the User is told about each such Enrollment, as it
     * is acted upon from its own page.
     *
     * @throws ValidationException when the reason is missing or the request cannot be rejected
     */
    public function __invoke(LicenceRequest $licenceRequest, User $organizer, string $reason): LicenceRequest
    {
        Validator::make(['rejection_reason' => $reason], ['rejection_reason' => 'required|string|max:1000'])->validate();

        $reviewed = DB::transaction(function () use ($licenceRequest, $organizer, $reason) {
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

        $reviewed->user->notify(new LicenceRequestRejected($reviewed));

        $reviewed->enrollments()
            ->active()
            ->with('user')
            ->get()
            ->each(fn (Enrollment $enrollment) => $enrollment->user->notify(new EnrollmentNeedsAction($enrollment, needsLicence: true)));

        return $reviewed;
    }
}
