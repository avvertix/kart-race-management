<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RejectEnrollment
{
    /**
     * Reject a submitted Enrollment with a reason the driver reads, while no payment proof waits for review. Releases its
     * race numbers and cancels its unpaid Order.
     *
     * @throws ValidationException when the reason is missing or the Enrollment is not submitted
     */
    public function __invoke(Enrollment $enrollment, string $reason): Enrollment
    {
        Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($enrollment, $reason) {
            $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'enrollment', __('Only a submitted enrollment can be rejected.'));

            if ($locked->hasPaymentUnderReview()) {
                throw ValidationException::withMessages(['enrollment' => __('A payment proof waits for review: confirm or reject it before rejecting the enrollment.')]);
            }

            $locked->update([
                'status' => EnrollmentStatus::Rejected,
                'rejection_reason' => $reason,
            ]);

            $locked->releaseNumbersAndCancelUnpaidOrder();

            return $locked;
        });
    }
}
