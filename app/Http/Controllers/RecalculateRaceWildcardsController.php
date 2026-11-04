<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Wildcard\RecalculateWildcards;
use App\Models\Race;
use Illuminate\Http\RedirectResponse;

class RecalculateRaceWildcardsController extends Controller
{
    /**
     * Evaluate again the wildcard status of every participant of the race, also when the race has results, as the
     * `participants:recalculate-wildcard` command does.
     */
    public function __invoke(Race $race, RecalculateWildcards $recalculateWildcards): RedirectResponse
    {
        $this->authorize('update', $race);

        $results = $recalculateWildcards->forRace($race);

        return to_route('races.show', $race)
            ->with('flash.banner', __(':changed of :total participants changed wildcard status.', [
                'changed' => $results->where('changed', true)->count(),
                'total' => $results->count(),
            ]));
    }
}
