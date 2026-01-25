<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use Illuminate\Http\Request;

class UpdateChampionshipPaymentSettingsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Championship $championship)
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'registration_price' => 'required|integer|min:0',
            'bank' => 'required|string|min:1',
            'bank_account' => 'required|string|min:1',
            'bank_holder' => 'nullable|string|min:1',
        ]);

        $championship->registration_price = $validated['registration_price'];

        $championship->payment->bank_account = $validated['bank_account'];
        $championship->payment->bank_name = $validated['bank'];
        $championship->payment->bank_holder = $validated['bank_holder'];

        $championship->save();

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship cost and payment updated.', [
                'championship' => $championship->title,
            ]));
    }
}
