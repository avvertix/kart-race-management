<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RaceCommunication;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RaceCommunicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('communication:list');
    }

    public function view(User $user, RaceCommunication $raceCommunication): bool
    {
        return $user->hasPermission('communication:view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('communication:create');
    }

    public function update(User $user, ?RaceCommunication $raceCommunication = null): bool
    {
        return $user->hasPermission('communication:update');
    }

    public function delete(User $user, RaceCommunication $raceCommunication): bool
    {
        return $user->getKey() === $raceCommunication->user_id
            || $user->isAdmin();
    }

    public function restore(User $user, RaceCommunication $raceCommunication): bool
    {
        return false;
    }

    public function forceDelete(User $user, RaceCommunication $raceCommunication): bool
    {
        return false;
    }
}
