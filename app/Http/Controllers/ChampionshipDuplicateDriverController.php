<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FindDuplicateDrivers;
use App\Actions\MergeDuplicateDrivers;
use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChampionshipDuplicateDriverController extends Controller
{
    /**
     * Display participations that likely belong to the same driver.
     */
    public function index(Championship $championship, FindDuplicateDrivers $findDuplicateDrivers)
    {
        Gate::authorize('merge', Participant::class);

        return view('championship.duplicate-driver.index', [
            'championship' => $championship,
            'groups' => $findDuplicateDrivers($championship),
        ]);
    }

    /**
     * Merge the selected participations into the canonical one.
     */
    public function store(Request $request, Championship $championship, MergeDuplicateDrivers $mergeDuplicateDrivers)
    {
        Gate::authorize('merge', Participant::class);

        $participantExists = Rule::exists('participants', 'uuid')->where('championship_id', $championship->getKey());

        $validated = $this->validate($request, [
            'canonical' => ['required', 'string', $participantExists],
            'duplicates' => ['required', 'array', 'min:1'],
            'duplicates.*' => ['required', 'string', $participantExists],
        ]);

        $canonical = Participant::where('uuid', $validated['canonical'])->firstOrFail();

        $duplicates = Participant::whereIn('uuid', $validated['duplicates'])->get();

        $mergeDuplicateDrivers($canonical, $duplicates);

        return to_route('championships.duplicate-drivers.index', $championship)
            ->with('flash.banner', __('Participations of :driver merged.', [
                'driver' => $canonical->full_name,
            ]));
    }
}
