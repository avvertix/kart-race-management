<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TemplateDriver;
use App\Models\User;

class TemplateDriverPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('drivers:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TemplateDriver $templateDriver): bool
    {
        return $user->hasPermission('drivers:view') && $user->id === $templateDriver->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('drivers:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TemplateDriver $templateDriver): bool
    {
        return $user->hasPermission('drivers:update') && $user->id === $templateDriver->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TemplateDriver $templateDriver): bool
    {
        return $user->hasPermission('drivers:delete') && $user->id === $templateDriver->user_id;
    }
}
