<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentConfirmed;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\EnrollmentConfirmed as EnrollmentConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmEnrollment
{
    /**
     * Confirm a submitted Enrollment with nothing missing, see Enrollment::missingForConfirmation(). Confirms the race
     * numbers picked with it.
     *
     * @throws ValidationException when the Enrollment is not submitted or something is still missing
     */
    public function __invoke(Enrollment $enrollment, User $organizer): Enrollment
    {
        $enrollment = DB::transaction(function () use ($enrollment, $organizer) {
            $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'enrollment', __('Only a submitted enrollment can be confirmed.'));

            $missing = $locked->missingForConfirmation();

            if ($missing !== []) {
                throw ValidationException::withMessages(['enrollment' => $missing]);
            }

            $locked->update([
                'status' => EnrollmentStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => $organizer->getKey(),
            ]);

            $locked->reservations()->where('status', BibReservationStatus::Pending)->update(['status' => BibReservationStatus::Confirmed]);

            return $locked;
        });

        EnrollmentConfirmed::dispatch($enrollment);

        $enrollment->user->notify(new EnrollmentConfirmedNotification($enrollment));

        return $enrollment;
    }
}
