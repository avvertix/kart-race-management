<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use Illuminate\Http\Request;

class UpdateChampionshipBonusSettingsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Championship $championship)
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'fixed_bonus_amount' => 'required|integer|min:100|max:'.($championship->registration_price ?? config('races.price')),
        ]);

        $championship->bonuses->fixed_bonus_amount = (int) ($validated['fixed_bonus_amount']);

        $championship->save();

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship bonus configuration updated.', [
                'championship' => $championship->title,
            ]));
    }
}
