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
            'shared_bib' => ['nullable', 'integer', 'min:1', 'max:5000', 'required_with:shared_bib_licences'],
            'shared_bib_licences' => ['nullable', 'string'],
        ]);

        $sharedBibLicences = collect(preg_split('/[\r\n,]+/', $validated['shared_bib_licences'] ?? '', -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($licence) => mb_trim($licence))
            ->filter()
            ->values()
            ->all();

        $championship->registration_settings->allow_different_bibs = ($validated['allow_different_bibs'] ?? 'false') === 'true';
        $championship->registration_settings->shared_bib_licences = $sharedBibLicences;
        $championship->registration_settings->shared_bib = empty($sharedBibLicences) ? null : (int) $validated['shared_bib'];

        $championship->save();

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship BIB settings updated.', [
                'championship' => $championship->title,
            ]));
    }
}
