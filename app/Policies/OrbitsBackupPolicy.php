<?php

namespace App\Policies;

use App\Models\OrbitsBackup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrbitsBackupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orbits-backup:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrbitsBackup $orbitsBackup): bool
    {
        return $user->hasPermission('orbits-backup:view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('orbits-backup:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OrbitsBackup $orbitsBackup): bool
    {
        return $user->hasPermission('orbits-backup:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrbitsBackup $orbitsBackup): bool
    {
        return $user->hasPermission('orbits-backup:delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OrbitsBackup $orbitsBackup): bool
    {
        return $user->hasPermission('orbits-backup:delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OrbitsBackup $orbitsBackup): bool
    {
        return $user->hasPermission('orbits-backup:delete');
    }
}
