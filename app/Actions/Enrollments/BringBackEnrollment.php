<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BringBackEnrollment
{
    public function __construct(private GrantEnrollmentEntitlements $grantEnrollmentEntitlements) {}

    /**
     * The organizer returns a withdrawn Enrollment to submitted, see Enrollment::canBeBroughtBack(), while the driver has
     * no other active Enrollment in the championship: it moves to the Licence emitted meanwhile from the request it waited
     * for, or covers that request again when its option covers the renewal. The race numbers released on withdrawal are
     * not restored: the driver or the organizer picks one again.
     *
     * @throws ValidationException when the Enrollment can't come back
     */
    public function __invoke(Enrollment $enrollment): Enrollment
    {
        $broughtBack = DB::transaction(function () use ($enrollment) {
            $championship = Championship::query()->whereKey($enrollment->championship_id)->lockForUpdate()->firstOrFail();

            return $this->bringBack($championship, $enrollment);
        });

        ($this->grantEnrollmentEntitlements)($broughtBack);

        return $broughtBack;
    }

    /**
     * Return the Enrollment to submitted when it is withdrawn, leaving it as it is otherwise, e.g. when a payment is
     * recorded on an active one. Call it inside a transaction, after locking the championship; grant the race fee
     * discount afterwards.
     *
     * @throws ValidationException when it is withdrawn but can't come back
     */
    public function bringBackIfWithdrawn(Championship $championship, Enrollment $enrollment): Enrollment
    {
        $locked = Enrollment::query()->whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

        return $locked->status === EnrollmentStatus::Withdrawn ? $this->bringBack($championship, $locked) : $locked;
    }

    /**
     * Return the withdrawn Enrollment to submitted. Call it inside a transaction, after locking the championship.
     *
     * @throws ValidationException
     */
    private function bringBack(Championship $championship, Enrollment $enrollment): Enrollment
    {
        $locked = Enrollment::query()->whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

        if (! $locked->canBeBroughtBack()) {
            throw ValidationException::withMessages(['payment' => __('The enrollment can\'t come back: the first race started, or what was paid is already a promo code.')]);
        }

        $waitedFor = $locked->licenceRequest;

        if ($locked->licence === null && $waitedFor?->status === LicenceRequestStatus::Emitted && $waitedFor->licence !== null) {
            $locked->update(['licence_id' => $waitedFor->licence->getKey(), 'licence_request_id' => null]);
            $locked->refresh();
            $waitedFor = null;
        }

        Enrollment::ensureNotEnrolled($championship, $locked->licence, $locked->licence === null ? $waitedFor : null, $locked);

        $locked->update(['status' => EnrollmentStatus::Submitted]);

        if ($locked->licence === null && $waitedFor !== null && $locked->option?->covers_licence_renewal && $waitedFor->status !== LicenceRequestStatus::Rejected) {
            $waitedFor->coverWith($locked);
        }

        return $locked;
    }
}
