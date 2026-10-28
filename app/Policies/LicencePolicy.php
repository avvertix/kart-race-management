<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Licence;
use App\Models\User;

class LicencePolicy
{
    /**
     * Determine whether the user can list all licences.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('licence:list');
    }

    /**
     * Determine whether the user can view the licence. Users only see the licences they manage.
     */
    public function view(User $user, Licence $licence): bool
    {
        if ($user->hasPermission('licence:list')) {
            return $user->hasPermission('licence:view');
        }

        return $user->hasPermission('licence:view') && $this->manages($user, $licence);
    }

    /**
     * Determine whether the user can declare licences.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('licence:create');
    }

    /**
     * Determine whether the user can change the licence: only declared licences they manage, before verification.
     */
    public function update(User $user, Licence $licence): bool
    {
        return $user->hasPermission('licence:update')
            && $this->manages($user, $licence)
            && $licence->isAwaitingVerification();
    }

    /**
     * Determine whether the user can remove the licence: only declared licences they manage, before verification.
     */
    public function delete(User $user, Licence $licence): bool
    {
        return $user->hasPermission('licence:delete')
            && $this->manages($user, $licence)
            && $licence->isAwaitingVerification();
    }

    /**
     * Determine whether the user can mark a declared licence as verified.
     */
    public function verify(User $user, Licence $licence): bool
    {
        return $user->hasPermission('licence:list')
            && $user->hasPermission('licence:update')
            && $licence->isAwaitingVerification();
    }

    private function manages(User $user, Licence $licence): bool
    {
        return $licence->user_id === $user->getKey();
    }
}
