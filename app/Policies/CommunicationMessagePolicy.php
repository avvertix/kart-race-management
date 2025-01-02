<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CommunicationMessage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunicationMessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasPermission('communication:list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, CommunicationMessage $communicationMessage)
    {
        return $user->hasPermission('communication:view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermission('communication:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, CommunicationMessage $communicationMessage)
    {
        return $user->hasPermission('communication:update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, CommunicationMessage $communicationMessage)
    {
        return $user->hasPermission('communication:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, CommunicationMessage $communicationMessage)
    {
        return $user->hasPermission('communication:delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, CommunicationMessage $communicationMessage)
    {
        return $user->hasPermission('communication:delete');
    }
}
