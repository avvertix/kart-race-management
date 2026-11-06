<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine whether the user can list the enrollments of a championship.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enrollment:list');
    }

    /**
     * Determine whether the user can view the enrollment. Users only see the ones they submitted.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->hasPermission('enrollment:list')) {
            return $user->hasPermission('enrollment:view');
        }

        return $user->hasPermission('enrollment:view') && $enrollment->user_id === $user->getKey();
    }

    /**
     * Determine whether the user can enroll their licences.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('enrollment:create');
    }

    /**
     * Determine whether the user can process enrollments as the organizer: confirm, reject, withdraw and enroll on behalf.
     */
    public function review(User $user): bool
    {
        return $user->hasPermission('enrollment:list') && $user->hasPermission('enrollment:update');
    }

    /**
     * Determine whether the user can pick or change the race number as the driver: their own enrollment, while
     * submitted and enrollment is open, when the option reserves a number.
     */
    public function pickRaceNumber(User $user, Enrollment $enrollment): bool
    {
        return $this->ownsSubmitted($user, $enrollment)
            && $enrollment->championship->enrollment->isOpen()
            && $enrollment->grantsBibReservation();
    }

    /**
     * Determine whether the user can give the enrollment another licence as its driver: their own, while submitted, once
     * the licence request it waited for was rejected.
     */
    public function changeLicence(User $user, Enrollment $enrollment): bool
    {
        return $this->ownsSubmitted($user, $enrollment) && $enrollment->needsLicence();
    }

    /**
     * Determine whether the user can name or change the Competitor: the driver of their own enrollment, or the
     * organizer, while it is submitted.
     */
    public function changeCompetitor(User $user, Enrollment $enrollment): bool
    {
        if ($this->review($user)) {
            return $enrollment->status === EnrollmentStatus::Submitted;
        }

        return $this->ownsSubmitted($user, $enrollment);
    }

    /**
     * Determine whether the user can withdraw the enrollment as its driver: only their own, while submitted and no
     * payment proof waits for the organizer.
     */
    public function withdraw(User $user, Enrollment $enrollment): bool
    {
        return $this->ownsSubmitted($user, $enrollment)
            && ! $enrollment->hasPaymentUnderReview();
    }

    /**
     * Determine whether the user can upload the payment proof of their own enrollment.
     */
    public function pay(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermission('enrollment:view') && $enrollment->user_id === $user->getKey();
    }

    /**
     * Whether the enrollment is the user's own, still submitted.
     */
    private function ownsSubmitted(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermission('enrollment:update')
            && $enrollment->user_id === $user->getKey()
            && $enrollment->status === EnrollmentStatus::Submitted;
    }
}
