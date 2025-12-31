<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CopyChampionshipTires;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CopyChampionshipTiresController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ChampionshipTire::class, 'tire_option');
    }
    
    /**
     * Show the form for copying tires from another championship.
     */
    public function create(Championship $championship)
    {
        $this->authorize('create', ChampionshipTire::class);

        $sourceChampionships = Championship::query()
            ->where('id', '!=', $championship->id)
            ->whereHas('tires')
            ->orderBy('title', 'ASC')
            ->get();

        return view('championship-tire.copy', [
            'championship' => $championship,
            'sourceChampionships' => $sourceChampionships,
        ]);
    }

    /**
     * Copy tires from another championship.
     */
    public function store(Request $request, Championship $championship, CopyChampionshipTires $copyTires)
    {
        $this->authorize('create', ChampionshipTire::class);

        $validated = $request->validate([
            'source_championship' => [
                'required',
                'integer',
                Rule::exists((new Championship())->getTable(), 'id'),
            ],
        ]);

        $sourceChampionship = Championship::findOrFail($validated['source_championship']);

        $copiedTires = $copyTires($sourceChampionship, $championship);

        return redirect()->route('championships.tire-options.index', $championship)
            ->with('flash.banner', __(':count tire(s) copied successfully.', [
                'count' => $copiedTires->count(),
            ]));
    }
}
