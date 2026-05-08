<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChampionshipPenalty;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChampionshipPenaltyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('championship:list');
    }

    public function view(User $user, ChampionshipPenalty $championshipPenalty): bool
    {
        return $user->hasPermission('championship:list');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('championship:update');
    }

    public function update(User $user, ChampionshipPenalty $championshipPenalty): bool
    {
        return $user->hasPermission('championship:update');
    }

    public function delete(User $user, ChampionshipPenalty $championshipPenalty): bool
    {
        return $user->hasPermission('championship:update');
    }

    public function restore(User $user, ChampionshipPenalty $championshipPenalty): bool
    {
        return false;
    }

    public function forceDelete(User $user, ChampionshipPenalty $championshipPenalty): bool
    {
        return false;
    }
}
