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
    public function index(Championship $championship, CalculateAwardRanking $calculateRanking)
    {
        $awards = $championship->awards()
            ->with(['category', 'categories', 'races'])
            ->orderBy('name')
            ->get();

        $races = $championship->races()->orderBy('event_start_at')->get();

        $groupedAwards = $awards->groupBy(fn (ChampionshipAward $award) => $award->type->localizedName());

        $rankingsPerAward = $awards->mapWithKeys(
            fn (ChampionshipAward $award) => [$award->getKey() => $calculateRanking($award)]
        );

        return view('public-championship-award.index', [
            'championship' => $championship,
            'groupedAwards' => $groupedAwards,
            'rankingsPerAward' => $rankingsPerAward,
            'races' => $races,
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
