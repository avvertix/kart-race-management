<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\View\View;

/**
 * The page of a promo code, open to anyone with its link: the code, a QR code of the link, the championship, what it
 * gives and whether it can still be claimed. It shows no personal data.
 */
class PublicPromoCodeController extends Controller
{
    public function show(PromoCode $promoCode): View
    {
        $promoCode->load('championship');

        abort_unless($promoCode->championship->entitlements_enabled, 404);

        return view('public-promo-code.show', [
            'promoCode' => $promoCode,
        ]);
    }
}
