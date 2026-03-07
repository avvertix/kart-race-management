<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CalculateAwardRanking;
use App\Models\Championship;
use App\Models\ChampionshipAward;

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
    public function show(ChampionshipAward $award, CalculateAwardRanking $calculateRanking)
    {
        $award->load(['championship', 'category', 'categories', 'races']);

        $championship = $award->championship;
        $races = $championship->races()->orderBy('event_start_at')->get();

        $ranking = $calculateRanking($award);

        return view('public-championship-award.show', [
            'championship' => $championship,
            'award' => $award,
            'ranking' => $ranking,
            'races' => $races,
        ]);
    }
}
