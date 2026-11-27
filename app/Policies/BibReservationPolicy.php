<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BibReservationStatus;
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
     * Determine whether the user can update the model. A released reservation can't be changed.
     */
    public function update(User $user, BibReservation $bibReservation): bool
    {
        return $bibReservation->status->holdsTheNumber() && $user->hasPermission('championship:update');
    }

    /**
     * Determine whether the user can confirm a reservation picked by a driver.
     */
    public function confirm(User $user, BibReservation $bibReservation): bool
    {
        return $bibReservation->status === BibReservationStatus::Pending && $user->hasPermission('championship:update');
    }

    /**
     * Determine whether the user can release the reservation, freeing its number.
     */
    public function release(User $user, BibReservation $bibReservation): bool
    {
        return $this->update($user, $bibReservation);
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
