<?php

namespace App\Policies;

use App\Models\Championship;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChampionshipPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasPermission('championship:list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Championship $championship)
    {
        return $user->hasPermission('championship:list');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermission('championship:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Championship $championship)
    {
        return $user->hasPermission('championship:update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Championship $championship)
    {
        return $user->hasPermission('championship:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Championship $championship)
    {
        return $user->hasPermission('championship:restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Championship $championship)
    {
        return $user->hasPermission('championship:delete');
    }
}
