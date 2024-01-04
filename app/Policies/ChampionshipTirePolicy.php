<?php

namespace App\Policies;

use App\Models\ChampionshipTire;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChampionshipTirePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('category:list');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChampionshipTire $championshipTire): bool
    {
        return $user->hasPermission('category:list');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('category:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChampionshipTire $championshipTire): bool
    {
        return $user->hasPermission('category:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChampionshipTire $championshipTire): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ChampionshipTire $championshipTire): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ChampionshipTire $championshipTire): bool
    {
        return false;
    }
}
