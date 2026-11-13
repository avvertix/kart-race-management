<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;

/**
 * Early access to entering promo codes from the dashboard, still being prepared. Off by default: an admin enables it on
 * specific users from the user page. Admins always have it, guests never do.
 */
class PromoCodes
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
