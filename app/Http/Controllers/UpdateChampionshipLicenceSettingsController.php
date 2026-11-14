<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LicenceProvider;
use App\Models\Championship;
use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Support\LicenceCatalogue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateChampionshipLicenceSettingsController extends Controller
{
    public function edit(Championship $championship)
    {
        $this->authorize('update', $championship);

        return view('championship.licence-settings', [
            'championship' => $championship,
        ]);
    }

    public function update(Request $request, Championship $championship)
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'accepted_driver_licences' => 'nullable|array',
            'accepted_driver_licences.*' => ['integer', new Enum(DriverLicence::class)],
            'accepted_competitor_licences' => 'nullable|array',
            'accepted_competitor_licences.*' => ['integer', new Enum(CompetitorLicence::class)],
            'licence_provider' => ['nullable', 'string', Rule::enum(LicenceProvider::class)->only(LicenceCatalogue::selectableProviders($championship->licences->provider))],
        ]);

        $championship->licences->accepted_driver_licences = array_map('intval', $validated['accepted_driver_licences'] ?? []);
        $championship->licences->accepted_competitor_licences = array_map('intval', $validated['accepted_competitor_licences'] ?? []);

        if ($request->has('licence_provider')) {
            $championship->licences->provider = $request->enum('licence_provider', LicenceProvider::class);
        }

        $championship->save();

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship accepted licences updated.', [
                'championship' => $championship->title,
            ]));
    }
}
