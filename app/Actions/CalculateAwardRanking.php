<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AwardRankingMode;
use App\Models\ChampionshipAward;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\WildcardFilter;
use Illuminate\Database\Eloquent\Builder;
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
        $raceIds = $this->resolveRaceIds($award);

        $query = $this->buildBaseQuery($raceIds)
            ->where('participant_results.category_id', $award->category_id);

        $this->applyWildcardFilter($query, $award->wildcard_filter);

        $perRacePoints = $this->fetchPerRacePoints($query);

        if ($award->ranking_mode === AwardRankingMode::BestN) {
            return $this->rankByBestN($perRacePoints, $award->best_n);
        }

        return $this->rankByTotal($perRacePoints);
    }

    private function calculateOverallRanking(ChampionshipAward $award): Collection
    {
        $categoryIds = $award->categories()->pluck('categories.id');
        $raceIds = Race::where('championship_id', $award->championship_id)->pluck('id');

        $perRacePoints = $this->fetchPerRacePoints(
            $this->buildBaseQuery($raceIds)->whereIn('participant_results.category_id', $categoryIds)
        );

        return $this->rankByTotal($perRacePoints);
    }

    /**
     * Resolve the race IDs in scope for this award.
     * SpecificRaces mode uses the award's selected races; all other modes use every race in the championship.
     */
    private function resolveRaceIds(ChampionshipAward $award): Collection
    {
        if ($award->ranking_mode === AwardRankingMode::SpecificRaces) {
            return $award->races()->pluck('races.id');
        }

        return Race::where('championship_id', $award->championship_id)->pluck('id');
    }

    /**
     * Build a base query with participant_results joined to run_results and participants.
     * The join on participants implicitly excludes unlinked results (null participant_id).
     *
     * @param  Collection<int, mixed>  $raceIds
     */
    private function buildBaseQuery(Collection $raceIds): Builder
    {
        return ParticipantResult::query()
            ->join('run_results', 'run_results.id', '=', 'participant_results.run_result_id')
            ->join('participants', 'participants.id', '=', 'participant_results.participant_id')
            ->whereIn('run_results.race_id', $raceIds);
    }

    private function applyWildcardFilter(Builder $query, WildcardFilter $filter): void
    {
        if ($filter === WildcardFilter::OnlyWildcards) {
            $query->where('participants.wildcard', true);
        } elseif ($filter === WildcardFilter::ExcludeWildcards) {
            $query->where('participants.wildcard', false);
        }
    }

    /**
     * Aggregate points in the database to one row per participant per race.
     * This avoids fetching and grouping individual run-result rows in PHP.
     */
    private function fetchPerRacePoints(Builder $query): Collection
    {
        return $query
            ->selectRaw('
                participant_results.participant_id,
                participants.racer_hash,
                participants.first_name,
                participants.last_name,
                participants.bib,
                run_results.race_id,
                SUM(participant_results.points) as race_points
            ')
            ->groupBy(
                'participant_results.participant_id',
                'participants.racer_hash',
                'participants.first_name',
                'participants.last_name',
                'participants.bib',
                'run_results.race_id',
            )
            ->get();
    }

    private function rankByTotal(Collection $perRacePoints): Collection
    {
        return $perRacePoints
            ->groupBy('racer_hash')
            ->map(function (Collection $raceRows) {
                $first = $raceRows->first();
                $pointsPerRace = $raceRows->mapWithKeys(fn ($row) => [$row->race_id => (float) $row->race_points]);

                return [
                    'participant_id' => $first->participant_id,
                    'racer_hash' => $first->racer_hash,
                    'first_name' => str()->title($first->first_name),
                    'last_name' => str()->title($first->last_name),
                    'bib' => $first->bib,
                    'total_points' => $pointsPerRace->sum(),
                    'races_counted' => $raceRows->count(),
                    'points_per_race' => $pointsPerRace->all(),
                ];
            })
            ->sortByDesc('total_points')
            ->values();
    }

    private function rankByBestN(Collection $perRacePoints, int $bestN): Collection
    {
        return $perRacePoints
            ->groupBy('racer_hash')
            ->map(function (Collection $raceRows) use ($bestN) {
                $first = $raceRows->first();
                $allPointsPerRace = $raceRows
                    ->mapWithKeys(fn ($row) => [$row->race_id => (float) $row->race_points])
                    ->sortDesc();

                $bestRaceIds = $allPointsPerRace->keys()->take($bestN);

                return [
                    'participant_id' => $first->participant_id,
                    'racer_hash' => $first->racer_hash,
                    'first_name' => str()->title($first->first_name),
                    'last_name' => str()->title($first->last_name),
                    'bib' => $first->bib,
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
