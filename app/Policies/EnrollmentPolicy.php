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
     * submitted, when the championship reserves a number with enrollment.
     */
    public function pickRaceNumber(User $user, Enrollment $enrollment): bool
    {
        return $this->withdraw($user, $enrollment)
            && $enrollment->championship->enrollment->grants_bib_reservation;
    }

    /**
     * Determine whether the user can withdraw the enrollment as its driver: only their own, while submitted.
     */
    public function withdraw(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermission('enrollment:update')
            && $enrollment->user_id === $user->getKey()
            && $enrollment->status === EnrollmentStatus::Submitted;
    }
}
