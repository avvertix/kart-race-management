<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CalculateAwardRanking;
use App\Models\Championship;
use App\Models\ChampionshipAward;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PublicChampionshipAwardController extends Controller
{
    /**
     * Display all awards for a championship grouped by type.
     */
    public function index(Championship $championship)
    {
        $awards = $championship->awards()
            ->with(['category'])
            ->orderBy('name')
            ->get();

        $groupedAwards = $awards->groupBy(fn (ChampionshipAward $award) => $award->type->localizedName());

        return view('public-championship-award.index', [
            'championship' => $championship,
            'groupedAwards' => $groupedAwards,
        ]);
    }

    /**
     * Display the ranking for a single award.
     */
    public function show(Request $request, ChampionshipAward $award, CalculateAwardRanking $calculateRanking)
    {
        $award->load(['championship', 'category', 'categories', 'races']);

        $championship = $award->championship;
        $races = $championship->races()->orderBy('event_start_at')->get();

        $ranking = $calculateRanking($award);

        if ($request->input('format') === 'pdf') {

            return Pdf::loadView('public-championship-award.show-pdf', [
                'championship' => $championship,
                'award' => $award,
                'ranking' => $ranking,
                'races' => $races,
            ])
                ->setPaper('a4', 'landscape')
                ->addInfo([
                    'Title' => $award->name,
                    'Author' => config('app.name'),
                    'Creator' => config('app.name'),
                    'PDFProducer' => config('app.name'),
                ])
                ->stream($award->name.'.pdf');
        }

        return view('public-championship-award.show', [
            'championship' => $championship,
            'award' => $award,
            'ranking' => $ranking,
            'races' => $races,
        ]);
    }
}
