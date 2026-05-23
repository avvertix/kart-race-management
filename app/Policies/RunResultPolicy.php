<?php

namespace App\Policies;

use App\Models\RunResult;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RunResultPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('results:list');
    }

    /**
     * Determine whether the user can update any model (bulk operations).
     */
    public function updateAny(User $user): bool
    {
        return $user->hasPermission('results:update');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RunResult $runResult): bool
    {
        return $user->hasPermission('results:view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('results:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RunResult $runResult): bool
    {
        return $user->hasPermission('results:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RunResult $runResult): bool
    {
        return $user->hasPermission('results:delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RunResult $runResult): bool
    {
        return $user->hasPermission('results:delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RunResult $runResult): bool
    {
        return $user->hasPermission('results:delete');
    }
}
