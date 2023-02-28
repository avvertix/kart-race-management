<?php

namespace App\Policies;

use App\Models\Transponder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransponderPolicy
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
        return $user->hasPermission('transponder:list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transponder  $transponder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Transponder $transponder)
    {
        return $user->hasPermission('transponder:list');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermission('transponder:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transponder  $transponder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Transponder $transponder)
    {
        return $user->hasPermission('transponder:update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transponder  $transponder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Transponder $transponder)
    {
        return $user->hasPermission('transponder:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transponder  $transponder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Transponder $transponder)
    {
        return $user->hasPermission('transponder:restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transponder  $transponder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Transponder $transponder)
    {
        return $user->hasPermission('transponder:delete');
    }
}
