<?php

declare(strict_types=1);

namespace App\Actions\PromoCodes;

use App\Enums\PromoCodeStatus;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\PromoCode;
use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimPromoCode
{
    /**
     * Claim the code once and for all, for a Licence or for the hash of a licence number typed in a race registration,
     * turning it into an Entitlement. The code is locked, so only one claim succeeds also under concurrent requests.
     *
     * @throws ValidationException when the code is not unclaimed or its championship has no Entitlements
     */
    public function __invoke(PromoCode $promoCode, string $licenceHash, ?Licence $licence = null, ?User $user = null): Entitlement
    {
        return DB::transaction(function () use ($promoCode, $licenceHash, $licence, $user) {
            $locked = PromoCode::query()->whereKey($promoCode->getKey())->lockForUpdate()->firstOrFail();

            self::ensureUnclaimed($locked);

            if (! $locked->championship->entitlements_enabled) {
                throw ValidationException::withMessages(['promo_code' => __('The championship doesn\'t accept promo codes.')]);
            }

            $locked->update([
                'status' => PromoCodeStatus::Claimed,
                'claimed_at' => now(),
                'claimed_by_user_id' => $user?->getKey(),
                'licence_id' => $licence?->getKey(),
                'licence_hash' => $licenceHash,
            ]);

            return Entitlement::create([
                'championship_id' => $locked->championship_id,
                'licence_id' => $licence?->getKey(),
                'licence_hash' => $licenceHash,
                'mode' => $locked->mode,
                'amount' => $locked->amount,
                'remaining' => $locked->amount,
                'source_type' => $locked->getMorphClass(),
                'source_id' => $locked->getKey(),
            ]);
        });
    }

    /**
     * The code typed in a race registration, when it can be claimed there: it exists, belongs to the race's championship
     * and the race accepts promo codes. Whether it is still unclaimed is checked again when claiming.
     *
     * @throws ValidationException
     */
    public static function forRace(string $typed, Race $race): PromoCode
    {
        $promoCode = PromoCode::findByCode($typed);

        if ($promoCode === null || $promoCode->championship_id !== $race->championship_id) {
            throw ValidationException::withMessages(['promo_code' => __('The promo code is not valid for this championship.')]);
        }

        if (! $race->isEntitlementEnabled()) {
            throw ValidationException::withMessages(['promo_code' => __('Promo codes don\'t apply to this race.')]);
        }

        self::ensureUnclaimed($promoCode);

        return $promoCode;
    }

    /**
     * Refuse a code already claimed or revoked.
     *
     * @throws ValidationException
     */
    public static function ensureUnclaimed(PromoCode $promoCode): void
    {
        if ($promoCode->status !== PromoCodeStatus::Unclaimed) {
            throw ValidationException::withMessages(['promo_code' => self::notClaimableMessage($promoCode->status)]);
        }
    }

    private static function notClaimableMessage(PromoCodeStatus $status): string
    {
        return $status === PromoCodeStatus::Revoked
            ? __('This code was revoked and can no longer be claimed.')
            : __('This code has already been claimed.');
    }
}
