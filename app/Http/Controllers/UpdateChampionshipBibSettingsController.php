<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use Illuminate\Http\Request;

class UpdateChampionshipBibSettingsController extends Controller
{
    public function __invoke(Request $request, Championship $championship)
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'allow_different_bibs' => ['nullable', 'in:true,false'],
        ]);

        $championship->registration_settings->allow_different_bibs = ($validated['allow_different_bibs'] ?? 'false') === 'true';

        $championship->save();

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship BIB settings updated.', [
                'championship' => $championship->title,
            ]));
    }
}
