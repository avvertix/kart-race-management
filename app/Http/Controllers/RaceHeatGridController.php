<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildRaceHeatGrids;
use App\Models\Race;
use App\Models\RunResult;
use Illuminate\Http\Request;

class RaceHeatGridController extends Controller
{
    /**
     * Show, for each category, the heats assignment from the qualifying
     * and the pre-final grid from the heats results.
     */
    public function index(Request $request, Race $race, BuildRaceHeatGrids $buildGrids)
    {
        $this->authorize('view', $race);
        $this->authorize('viewAny', RunResult::class);

        $race->load('championship');

        return view('race-result.heat-grids', [
            'race' => $race,
            'championship' => $race->championship,
            'grid' => $buildGrids($race, $request->integer('heats') ?: null),
            'prefinale' => $buildGrids->prefinale($race),
        ]);
    }
}
