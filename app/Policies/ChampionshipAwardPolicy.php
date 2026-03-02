<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChampionshipAward;
use App\Models\User;

class ChampionshipAwardPolicy
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
    public function view(User $user, ChampionshipAward $championshipAward): bool
    {
        return $user->hasPermission('championship:list');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('championship:update');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChampionshipAward $championshipAward): bool
    {
        return $user->hasPermission('championship:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChampionshipAward $championshipAward): bool
    {
        return $user->hasPermission('championship:update');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ChampionshipAward $championshipAward): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ChampionshipAward $championshipAward): bool
    {
        return false;
    }
}
