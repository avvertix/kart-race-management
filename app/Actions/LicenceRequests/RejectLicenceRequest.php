<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Actions\Enrollments\RejectEnrollment;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Enrollment;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Notifications\LicenceRequestRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RejectLicenceRequest
{
    /**
     * Close the request without a licence, with a reason shown to the requester. Refunds are settled by email. The
     * Enrollments packaged with the request are rejected too, releasing their race numbers; nothing is granted.
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

        Enrollment::query()
            ->where('licence_request_id', $reviewed->getKey())
            ->whereNotNull('order_id')
            ->where('order_id', $reviewed->covering_order_id)
            ->where('status', EnrollmentStatus::Submitted)
            ->get()
            ->each(fn (Enrollment $enrollment) => app(RejectEnrollment::class)($enrollment, __('The licence request paid together with it was rejected.')));

        $reviewed->user->notify(new LicenceRequestRejected($reviewed));

        return $reviewed;
    }
}
