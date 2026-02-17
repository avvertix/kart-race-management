<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\RunResult;

class PublicRaceResultController extends Controller
{
    /**
     * Display published results for a given race.
     */
    public function index(Race $race)
    {
        $race->load('championship');

        $groupedRunResults = $race->results()
            ->whereNotNull('published_at')
            ->withCount('participantResults')
            ->orderBy('run_type')
            ->get()
            ->groupBy(fn ($result) => $result->run_type->localizedName());

        return view('public-race-result.index', [
            'race' => $race,
            'championship' => $race->championship,
            'groupedRunResults' => $groupedRunResults,
        ]);
    }

    /**
     * Display a single published run result with participant results.
     */
    public function show(RunResult $result)
    {
        abort_unless($result->isPublished(), 404);

        $result->load(['race.championship', 'participantResults']);

        return view('public-race-result.show', [
            'race' => $result->race,
            'championship' => $result->race->championship,
            'runResult' => $result,
            'participantResults' => $result->participantResults,
        ]);
    }
}
