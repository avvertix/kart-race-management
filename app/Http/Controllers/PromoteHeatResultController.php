<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PromoteHeatResult;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PromoteHeatResultController extends Controller
{
    /**
     * Score and publish a heat as the pre-final or the final.
     */
    public function store(Request $request, RunResult $result, PromoteHeatResult $promote): RedirectResponse
    {
        $result->load('race');

        $this->authorize('update', $result);

        $validated = $this->validate($request, [
            'run_type' => ['required', Rule::in([RunType::RACE_1->value, RunType::RACE_2->value])],
        ]);

        try {
            $promote($result, RunType::from((int) $validated['run_type']));
        } catch (InvalidArgumentException $e) {
            return redirect()->route('races.results.index', $result->race)->withErrors(['promote' => $e->getMessage()]);
        }

        return redirect()->route('races.results.index', $result->race)
            ->with('flash.banner', __('Heat promoted.'));
    }

    /**
     * Bring a promoted heat back to a working session, without points and not published.
     */
    public function destroy(RunResult $result, PromoteHeatResult $promote): RedirectResponse
    {
        $result->load('race');

        $this->authorize('update', $result);

        try {
            $promote($result, null);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('races.results.index', $result->race)->withErrors(['promote' => $e->getMessage()]);
        }

        return redirect()->route('races.results.index', $result->race)
            ->with('flash.banner', __('Heat promotion removed.'));
    }
}
