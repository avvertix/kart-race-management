<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;
use Laravel\Pennant\Attributes\Name;

/**
 * Early access to licences, licence requests and enrollments, built for a future championship. Off by default: an
 * admin enables it on specific users from the user page. Admins always have it, guests never do; once the instance
 * goes public (LICENCES_AND_ENROLLMENTS_PUBLIC) everyone has it, guests included.
 *
 * Stored under its former name, so the users enabled before the rename keep it.
 */
#[Name('App\Features\LicencesAndProducts')]
class LicencesAndEnrollments
{
    /**
     * Everyone has the feature on a public instance, and admins always do, whatever is stored for them.
     */
    public function before(?User $user): ?bool
    {
        if (config('licences.public') || $user?->isAdmin()) {
            return true;
        }

        return $user === null ? false : null;
    }

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(?User $user): bool
    {
        return false;
    }
}
