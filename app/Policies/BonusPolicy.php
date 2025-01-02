<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Bonus;
use App\Models\User;

class BonusPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('bonus:list');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bonus $bonus): bool
    {
        return $user->hasPermission('bonus:list');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('bonus:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bonus $bonus): bool
    {
        return $user->hasPermission('bonus:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bonus $bonus): bool
    {
        return $user->hasPermission('bonus:delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bonus $bonus): bool
    {
        return $user->hasPermission('bonus:delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bonus $bonus): bool
    {
        return $user->hasPermission('bonus:delete');
    }
}
