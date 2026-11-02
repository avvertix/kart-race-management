<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;

/**
 * Early access to licences, licence requests, products and orders, built for a future championship. Off by default:
 * an admin enables it on specific users from the user page. Admins always have it. Guests never do, so the public
 * product pages stay hidden until it is enabled for everyone.
 */
class LicencesAndProducts
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
