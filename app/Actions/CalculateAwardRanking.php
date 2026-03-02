<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AwardRankingMode;
use App\Models\ChampionshipAward;
use App\Models\ParticipantResult;
use App\Models\WildcardFilter;
use Illuminate\Support\Collection;

class CalculateAwardRanking
{
    /**
     * Calculate the ranking for a given award.
     *
     * @return Collection<int, array{participant_id: int, first_name: string, last_name: string, bib: int, total_points: float, races_counted: int, points_per_race: array<int, float>}>
     */
    public function __invoke(ChampionshipAward $award): Collection
    {
        if ($award->isCategoryAward()) {
            return $this->calculateCategoryRanking($award);
        }

        return $this->calculateOverallRanking($award);
    }

    private function calculateCategoryRanking(ChampionshipAward $award): Collection
    {
        // Get all races within the championship

        // Get all races participant results for the award category, applying wildcard filter and ranking mode

        $query = ParticipantResult::query()
            ->whereNotNull('participant_id')
            ->where('category_id', $award->category_id)
            ->whereHas('runResult', function ($q) use ($award) {
                $q->whereHas('race', function ($q) use ($award) {
                    $q->where('championship_id', $award->championship_id);
                });
            });

        $this->applyWildcardFilter($query, $award->wildcard_filter);

        if ($award->ranking_mode === AwardRankingMode::SpecificRaces) {
            $raceIds = $award->races()->pluck('races.id');

            $query->whereHas('runResult', function ($q) use ($raceIds) {
                $q->whereIn('race_id', $raceIds);
            });
        }

        $results = $query
            ->with(['participant:id,first_name,last_name,bib,racer_hash', 'runResult:id,race_id'])
            ->get();

        if ($award->ranking_mode === AwardRankingMode::BestN) {
            return $this->rankByBestN($results, $award->best_n);
        }

        return $this->rankByTotal($results);
    }

    private function calculateOverallRanking(ChampionshipAward $award): Collection
    {
        $categoryIds = $award->categories()->pluck('categories.id');

        $results = ParticipantResult::query()
            ->whereNotNull('participant_id')
            ->whereIn('category_id', $categoryIds)
            ->whereHas('runResult', function ($q) use ($award) {
                $q->whereHas('race', function ($q) use ($award) {
                    $q->where('championship_id', $award->championship_id);
                });
            })
            ->with(['participant:id,first_name,last_name,bib,racer_hash', 'runResult:id,race_id'])
            ->get();

        return $this->rankByTotal($results);
    }

    private function applyWildcardFilter($query, WildcardFilter $filter): void
    {
        if ($filter === WildcardFilter::OnlyWildcards) {
            $query->whereHas('participant', fn ($q) => $q->where('wildcard', true));
        } elseif ($filter === WildcardFilter::ExcludeWildcards) {
            $query->whereHas('participant', fn ($q) => $q->where('wildcard', false));
        }
    }

    private function rankByTotal(Collection $results): Collection
    {
        return $results
            ->groupBy('participant.racer_hash')
            ->map(function (Collection $participantResults) {
                $participant = $participantResults->first()->participant;

                $pointsPerRace = $participantResults
                    ->groupBy('runResult.race_id')
                    ->map(fn (Collection $raceResults) => $raceResults->sum('points'))
                    ->all();

                return [
                    'participant_id' => $participant->id,
                    'racer_hash' => $participant->racer_hash,
                    'first_name' => str()->title($participant->first_name),
                    'last_name' => str()->title($participant->last_name),
                    'bib' => $participant->bib,
                    'total_points' => $participantResults->sum('points'),
                    'races_counted' => count($pointsPerRace),
                    'points_per_race' => $pointsPerRace,
                ];
            })
            ->sortByDesc('total_points')
            ->values();
    }

    private function rankByBestN(Collection $results, int $bestN): Collection
    {
        return $results
            ->groupBy('participant.racer_hash')
            ->map(function (Collection $participantResults) use ($bestN) {
                $participant = $participantResults->first()->participant;

                $allPointsPerRace = $participantResults
                    ->groupBy('runResult.race_id')
                    ->map(fn (Collection $raceResults) => $raceResults->sum('points'))
                    ->sortDesc();

                $bestRaceIds = $allPointsPerRace->keys()->take($bestN);

                return [
                    'participant_id' => $participant->id,
                    'racer_hash' => $participant->racer_hash,
                    'first_name' => str()->title($participant->first_name),
                    'last_name' => str()->title($participant->last_name),
                    'bib' => $participant->bib,
                    'total_points' => $allPointsPerRace->take($bestN)->sum(),
                    'races_counted' => $allPointsPerRace->take($bestN)->count(),
                    'points_per_race' => $allPointsPerRace->all(),
                    'counted_race_ids' => $bestRaceIds->values()->all(),
                ];
            })
            ->sortByDesc('total_points')
            ->values();
    }
}
