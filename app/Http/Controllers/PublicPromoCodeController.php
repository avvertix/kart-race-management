<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PromoCodeStatus;
use App\Models\Licence;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The page of a promo code, open to anyone with its link: the code, a QR code of the link, the championship, what it
 * gives, how much has been used and whether it can still be claimed. It shows no personal data. Signed-in Users can
 * claim it for one of their Licences.
 */
class PublicPromoCodeController extends Controller
{
    public function show(Request $request, PromoCode $promoCode): View
    {
        $promoCode->load(['championship', 'entitlement']);

        abort_unless($promoCode->championship->entitlements_enabled, 404);

        $user = $request->user();

        return view('public-promo-code.show', [
            'promoCode' => $promoCode,
            'claimableLicences' => $user === null || $promoCode->status !== PromoCodeStatus::Unclaimed
                ? collect()
                : Licence::query()->managedBy($user)->enrollableIn($promoCode->championship)->orderBy('id')->get(),
        ]);
    }
}
