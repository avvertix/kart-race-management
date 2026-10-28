<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LicenceType;
use App\Models\User;

class LicenceTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('licence-type:list');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LicenceType $licenceType): bool
    {
        return $user->hasPermission('licence-type:view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('licence-type:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LicenceType $licenceType): bool
    {
        return $user->hasPermission('licence-type:update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LicenceType $licenceType): bool
    {
        return $user->hasPermission('licence-type:delete');
    }
}
