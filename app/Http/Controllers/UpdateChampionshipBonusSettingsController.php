<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BonusMode;
use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class UpdateChampionshipBonusSettingsController extends Controller
{
    /**
     * Show the bonus settings form.
     */
    public function edit(Championship $championship)
    {
        $this->authorize('update', $championship);

        return view('bonus.settings', [
            'championship' => $championship,
        ]);
    }

    /**
     * Handle the incoming request.
     */
    public function update(Request $request, Championship $championship)
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'bonus_mode' => ['required', 'integer', new Enum(BonusMode::class)],
            'fixed_bonus_amount' => 'nullable|integer|min:100|max:'.($championship->registration_price ?? config('races.price')),
        ]);

        $bonusMode = BonusMode::from((int) $validated['bonus_mode']);

        $championship->bonuses->bonus_mode = $bonusMode;

        if ($bonusMode === BonusMode::CREDIT && isset($validated['fixed_bonus_amount'])) {
            $championship->bonuses->fixed_bonus_amount = (int) ($validated['fixed_bonus_amount']);
        }

        $championship->save();

        return to_route('championships.bonuses.index', $championship)
            ->with('flash.banner', __(':championship bonus configuration updated.', [
                'championship' => $championship->title,
            ]));
    }
}
