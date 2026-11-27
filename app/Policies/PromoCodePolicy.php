<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PromoCodeStatus;
use App\Models\PromoCode;
use App\Models\User;

class PromoCodePolicy
{
    /**
     * Determine whether the user can list the promo codes of a championship.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('promo-code:list');
    }

    /**
     * Determine whether the user can issue promo codes.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('promo-code:create');
    }

    /**
     * Determine whether the user can revoke the code: only while unclaimed.
     */
    public function revoke(User $user, PromoCode $promoCode): bool
    {
        return $user->hasPermission('promo-code:update') && $promoCode->status === PromoCodeStatus::Unclaimed;
    }
}
