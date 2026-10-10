<?php

declare(strict_types=1);

namespace App;

use App\Enums\Role;
use Illuminate\Support\Str;

trait HasRole
{
    /**
     * Get the user's role.
     */
    public function userRole(): ?Role
    {
        return $this->role ? Role::tryFrom($this->role) : null;
    }

    /**
     * Determine if the user has the given role.
     */
    public function hasRole(Role|string $role): bool
    {
        $role = $role instanceof Role ? $role : Role::tryFrom($role);

        return $role !== null && $this->userRole() === $role;
    }

    public function isAdmin(): bool
    {
        return $this->userRole() === Role::Admin;
    }

    /**
     * Get the user's permissions for the given role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return $this->userRole()?->permissions() ?? [];
    }

    /**
     * Determine if the user has the given permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions();

        return in_array($permission, $permissions) ||
               in_array('*', $permissions) ||
               (Str::endsWith($permission, ':create') && in_array('*:create', $permissions)) ||
               (Str::endsWith($permission, ':update') && in_array('*:update', $permissions));
    }
}
