<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BibReservation;
use App\Models\User;

class BibReservationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('championship:list');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BibReservation $bibReservation): bool
    {
        return $user->hasPermission('championship:list');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('championship:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BibReservation $bibReservation): bool
    {
        return $user->hasPermission('championship:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BibReservation $bibReservation): bool
    {
        return $user->hasPermission('championship:delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BibReservation $bibReservation): bool
    {
        return $user->hasPermission('championship:restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BibReservation $bibReservation): bool
    {
        return $user->hasPermission('championship:delete');
    }
}
