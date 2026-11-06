<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;
use Laravel\Pennant\Attributes\Name;

/**
 * Early access to licences, licence requests and enrollments, built for a future championship. Off by default: an
 * admin enables it on specific users from the user page. Admins always have it, guests never do.
 *
 * Stored under its former name, so the users enabled before the rename keep it.
 */
#[Name('App\Features\LicencesAndProducts')]
class LicencesAndEnrollments
{
    /**
     * Admins always have the feature, whatever is stored for them.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        return false;
    }
}
