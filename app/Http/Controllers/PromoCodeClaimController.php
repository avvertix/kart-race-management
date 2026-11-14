<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PromoCodes\ClaimPromoCode;
use App\Models\Licence;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Signed-in Users find a code they were given and claim it for one of their Licences.
 */
class PromoCodeClaimController extends Controller
{
    /**
     * Go to the page of the typed code, ignoring case and surrounding spaces.
     */
    public function find(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
        ]);

        $promoCode = PromoCode::findByCode($validated['code']);

        if ($promoCode === null) {
            throw ValidationException::withMessages(['code' => __('There is no promo code :code.', ['code' => PromoCode::normalize($validated['code'])])]);
        }

        return to_route('public.promo-codes.show', $promoCode);
    }

    /**
     * Claim the code for a driver Licence the User manages, valid in the year of the code's championship.
     */
    public function claim(Request $request, PromoCode $promoCode, ClaimPromoCode $claimPromoCode): RedirectResponse
    {
        ClaimPromoCode::ensureUnclaimed($promoCode);

        $user = $request->user();

        $validated = $request->validate([
            'licence' => ['required', 'string', Rule::in(Licence::query()->managedBy($user)->enrollableIn($promoCode->championship)->pluck('ulid'))],
        ]);

        $licence = Licence::query()->where('ulid', $validated['licence'])->sole();

        $claimPromoCode($promoCode, $licence->number_hash, $licence, $user);

        return to_route('public.promo-codes.show', $promoCode)
            ->with('flash.banner', __('Promo code claimed for :driver. It is applied automatically when registering for races of the championship.', [
                'driver' => $licence->holder->fullName(),
            ]));
    }
}
