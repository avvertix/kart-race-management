<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawEnrollment
{
    /**
     * Withdraw a submitted Enrollment, by its driver or the organizer, while no payment proof waits for review: a confirmed
     * one is final, so the driver never goes back to wildcard. Releases its race numbers and returns a Licence Request it
     * covered to its own payment. The organizer can bring it back by recording a payment before the first race, see
     * RecordEnrollmentPayment, or turn what was paid into race credit, see CreditEnrollmentPayment.
     *
     * @throws ValidationException when the Enrollment can't be withdrawn
     */
    public function __invoke(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'enrollment', __('Only a submitted enrollment can be withdrawn: a confirmed one is final.'));

            if ($locked->hasPaymentUnderReview()) {
                throw ValidationException::withMessages(['enrollment' => __('A payment proof waits for review: confirm or reject it before withdrawing.')]);
            }

            $locked->update(['status' => EnrollmentStatus::Withdrawn]);

            $locked->releaseNumbersAndCoverage();

            return $locked;
        });
    }
}
