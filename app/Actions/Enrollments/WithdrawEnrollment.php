<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentRevoked;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawEnrollment
{
    /**
     * Withdraw the Enrollment: the driver while it is submitted, the organizer also once confirmed. Releases its race
     * numbers and cancels its unpaid Order.
     *
     * @throws ValidationException when the Enrollment can't be withdrawn
     */
    public function __invoke(Enrollment $enrollment, bool $byOrganizer): Enrollment
    {
        $statuses = $byOrganizer ? EnrollmentStatus::active() : [EnrollmentStatus::Submitted];

        $message = $byOrganizer
            ? __('The enrollment is already closed.')
            : __('A confirmed enrollment can only be withdrawn by the organizer.');

        [$enrollment, $wasConfirmed] = DB::transaction(function () use ($enrollment, $statuses, $message) {
            $locked = $enrollment->lockInStatus($statuses, 'enrollment', $message);

            $wasConfirmed = $locked->status === EnrollmentStatus::Confirmed;

            $locked->update(['status' => EnrollmentStatus::Withdrawn]);

            $locked->releaseNumbersAndCancelUnpaidOrder();

            return [$locked, $wasConfirmed];
        });

        if ($wasConfirmed) {
            EnrollmentRevoked::dispatch($enrollment);
        }

        return $enrollment;
    }
}
