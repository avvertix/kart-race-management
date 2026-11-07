<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CombineHeatResults;
use App\Actions\CombineQualifyingSeries;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class RaceCombinedResultController extends Controller
{
    /**
     * Combine the qualifying series (groups) in a single qualifying.
     */
    public function qualifying(Race $race, CombineQualifyingSeries $combine): RedirectResponse
    {
        $this->authorize('create', RunResult::class);

        try {
            $combine($race);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('races.heat-grids.index', $race)->withErrors(['combine' => $e->getMessage()]);
        }

        return redirect()->route('races.results.index', $race)
            ->with('flash.banner', __('Combined qualifying created.'));
    }

    /**
     * Classify the heats as a race result.
     */
    public function heats(Request $request, Race $race, CombineHeatResults $combine): RedirectResponse
    {
        $this->authorize('create', RunResult::class);

        $validated = $this->validate($request, [
            'run_type' => ['required', Rule::in([RunType::RACE_1->value, RunType::RACE_2->value])],
        ]);

        try {
            $combine($race, RunType::from((int) $validated['run_type']));
        } catch (InvalidArgumentException $e) {
            return redirect()->route('races.heat-grids.index', $race)->withErrors(['combine' => $e->getMessage()]);
        }

        return redirect()->route('races.results.index', $race)
            ->with('flash.banner', __('Heats classification created.'));
    }
}
