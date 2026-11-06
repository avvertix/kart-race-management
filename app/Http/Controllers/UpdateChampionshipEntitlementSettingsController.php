<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UpdateChampionshipEntitlementSettingsController extends Controller
{
    /**
     * Turn Entitlements from promo codes and enrollment options on or off. A championship uses Entitlements or Bonuses, not both:
     * turning Entitlements on needs bonuses explicitly off, see docs/adr/0003-entitlements-separate-from-bonuses.md.
     */
    public function __invoke(Request $request, Championship $championship): RedirectResponse
    {
        $this->authorize('update', $championship);

        $request->validate([
            'entitlements_enabled' => ['nullable', 'boolean'],
        ]);

        $enabled = $request->boolean('entitlements_enabled');

        if ($enabled && $championship->bonus_enabled !== false) {
            throw ValidationException::withMessages([
                'entitlements_enabled' => __('Disable bonuses before enabling promo codes and enrollment discounts.'),
            ]);
        }

        $championship->entitlements_enabled = $enabled;
        $championship->save();

        return to_route('championships.edit', $championship)
            ->with('flash.banner', __(':championship discounts updated.', ['championship' => $championship->title]));
    }
}
