<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParticipantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasPermission('participant:list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Participant $participant)
    {
        return $user->hasPermission('participant:view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermission('participant:create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Participant $participant)
    {
        return $user->hasPermission('participant:update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Participant $participant)
    {
        return $user->hasPermission('participant:delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Participant $participant)
    {
        return $user->hasPermission('participant:restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Participant $participant)
    {
        return $user->hasPermission('participant:delete');
    }
}
