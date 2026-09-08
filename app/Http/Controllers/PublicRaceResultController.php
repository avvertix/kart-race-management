<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\RunResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
    public function show(Request $request, RunResult $result)
    {
        abort_unless($result->isPublished(), 404);

        $result->load(['race.championship', 'participantResults.participant']);

        $data = [
            'race' => $result->race,
            'championship' => $result->race->championship,
            'runResult' => $result,
            'participantResults' => $result->participantResults,
        ];

        if ($request->input('format') === 'pdf') {
            return Pdf::loadView('public-race-result.show-pdf', $data)
                ->setPaper('a4', 'portrait')
                ->addInfo([
                    'Title' => $result->title,
                    'Author' => config('app.name'),
                    'Creator' => config('app.name'),
                    'PDFProducer' => config('app.name'),
                ])
                ->stream($result->title.'.pdf');
        }

        return view('public-race-result.show', $data);
    }
}
