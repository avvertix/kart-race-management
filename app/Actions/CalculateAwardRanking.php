<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AwardRankingMode;
use App\Models\ChampionshipAward;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\ResultStatus;
use App\Models\RunType;
use App\Models\WildcardFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CalculateAwardRanking
{
    /**
     * Sessions considered when ranking a day award.
     *
     * @var array<int, RunType>
     */
    private const DAY_AWARD_RUN_TYPES = [RunType::QUALIFY, RunType::RACE_1, RunType::RACE_2];

    /**
     * Calculate the ranking for a given award.
     *
     * Day awards also include the `session_bonus` already added to `total_points`.
     *
     * @return Collection<int, array{participant_id: int, first_name: string, last_name: string, bib: int, total_points: float, races_counted: int, points_per_race: array<int, float>, session_bonus?: int}>
     */
    public function __invoke(ChampionshipAward $award, bool $publishedOnly = true): Collection
    {
        $query = $this->buildAwardQuery($award, $publishedOnly);

        return $this->rank($award, $this->fetchPerRacePoints($query, $award->isDayAward()));
    }

    /**
     * Build a detailed points breakdown for a single racer within an award: every run result
     * that contributed to their points, grouped by race, alongside the same totals shown in
     * the ranking so organizers can verify how a driver's score was calculated.
     *
     * @return array{
     *     total_points: float,
     *     races_counted: int,
     *     counted_race_ids: array<int, int>,
     *     points_per_race: array<int, float>,
     *     session_bonus: ?int,
     *     entries_by_race: Collection<int, Collection<int, array>>,
     * }
     */
    public function breakdown(ChampionshipAward $award, string $racerHash, bool $publishedOnly = false): array
    {
        $summaryQuery = $this->buildAwardQuery($award, $publishedOnly)
            ->where('participants.racer_hash', $racerHash);

        $summary = $this->rank($award, $this->fetchPerRacePoints($summaryQuery, $award->isDayAward()))->first();

        if ($summary === null) {
            return [
                'total_points' => 0.0,
                'races_counted' => 0,
                'counted_race_ids' => [],
                'points_per_race' => [],
                'session_bonus' => null,
                'entries_by_race' => collect(),
            ];
        }

        $entriesQuery = $this->buildAwardQuery($award, $publishedOnly)
            ->where('participants.racer_hash', $racerHash);

        return [
            'total_points' => $summary['total_points'],
            'races_counted' => $summary['races_counted'],
            'counted_race_ids' => $summary['counted_race_ids'] ?? array_keys($summary['points_per_race']),
            'points_per_race' => $summary['points_per_race'],
            'session_bonus' => $summary['session_bonus'] ?? null,
            'entries_by_race' => $this->fetchRunDetails($entriesQuery),
        ];
    }

    /**
     * Build the query selecting the participant results in scope for the award: its races,
     * categories and wildcard filter. Day awards only consider qualifying and race sessions.
     */
    private function buildAwardQuery(ChampionshipAward $award, bool $publishedOnly): Builder
    {
        $categoryIds = $award->isCategoryAward()
            ? collect([$award->category_id])
            : $award->categories()->pluck('categories.id');

        $query = $this->buildBaseQuery($this->resolveRaceIds($award), $publishedOnly)
            ->whereIn('participant_results.category_id', $categoryIds);

        $this->applyWildcardFilter($query, $award->wildcard_filter);

        if ($award->isDayAward()) {
            $query->whereIn('run_results.run_type', array_map(fn (RunType $type) => $type->value, self::DAY_AWARD_RUN_TYPES));
        }

        return $query;
    }

    private function rank(ChampionshipAward $award, Collection $perRacePoints): Collection
    {
        if ($award->isDayAward()) {
            return $this->rankByDay($perRacePoints);
        }

        if ($award->ranking_mode === AwardRankingMode::BestN) {
            return $this->rankByBestN($perRacePoints, $award->best_n);
        }

        return $this->rankByTotal($perRacePoints);
    }

    /**
     * Fetch the individual run-result rows behind a racer's points, grouped by race.
     */
    private function fetchRunDetails(Builder $query): Collection
    {
        return $query
            ->selectRaw('
                run_results.race_id as race_id,
                run_results.run_type as run_type_value,
                run_results.title as run_title,
                participant_results.points as points_value,
                participant_results.position as position_value,
                participant_results.status as status_value,
                participant_results.is_dnf as is_dnf_value,
                participant_results.is_dns as is_dns_value,
                participant_results.is_dq as is_dq_value,
                participant_results.category as category_label
            ')
            ->orderBy('run_results.race_id')
            ->orderBy('run_results.run_type')
            ->get()
            ->map(fn ($row) => [
                'run_type' => RunType::from((int) $row->run_type_value),
                'run_title' => $row->run_title,
                'points' => (float) $row->points_value,
                'position' => $row->position_value,
                'status' => ResultStatus::from((int) $row->status_value),
                'is_dnf' => (bool) $row->is_dnf_value,
                'is_dns' => (bool) $row->is_dns_value,
                'is_dq' => (bool) $row->is_dq_value,
                'category_label' => $row->category_label,
                'race_id' => (int) $row->race_id,
            ])
            ->groupBy('race_id');
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
    private function buildBaseQuery(Collection $raceIds, bool $publishedOnly): Builder
    {
        $query = ParticipantResult::query()
            ->join('run_results', 'run_results.id', '=', 'participant_results.run_result_id')
            ->join('participants', 'participants.id', '=', 'participant_results.participant_id')
            ->whereIn('run_results.race_id', $raceIds);

        if ($publishedOnly) {
            $query->whereNotNull('run_results.published_at');
        }

        return $query;
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
     *
     * With the session size, each row also carries the smallest number of entries
     * (all categories, DNS and DNF included) among the sessions the participant raced in.
     */
    private function fetchPerRacePoints(Builder $query, bool $withSessionSize = false): Collection
    {
        if ($withSessionSize) {
            $sessionSizes = ParticipantResult::query()
                ->selectRaw('run_result_id, COUNT(*) as session_size')
                ->groupBy('run_result_id');

            $query
                ->joinSub($sessionSizes, 'session_sizes', 'session_sizes.run_result_id', '=', 'participant_results.run_result_id')
                ->selectRaw('MIN(session_sizes.session_size) as session_size');
        }

        return $query
            ->selectRaw('
                participant_results.participant_id,
                participants.uuid,
                participants.racer_hash,
                participants.first_name,
                participants.last_name,
                participants.bib,
                run_results.race_id,
                SUM(participant_results.points) as race_points
            ')
            ->groupBy(
                'participant_results.participant_id',
                'participants.uuid',
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
                    'uuid' => $first->uuid,
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

    /**
     * Rank a day award: a single race where the session size is added once, as a bonus, to the points.
     */
    private function rankByDay(Collection $perRacePoints): Collection
    {
        return $perRacePoints
            ->groupBy('racer_hash')
            ->map(function (Collection $raceRows) {
                $first = $raceRows->first();
                $pointsPerRace = $raceRows->mapWithKeys(fn ($row) => [$row->race_id => (float) $row->race_points]);
                $sessionBonus = (int) $raceRows->min('session_size');

                return [
                    'participant_id' => $first->participant_id,
                    'uuid' => $first->uuid,
                    'racer_hash' => $first->racer_hash,
                    'first_name' => str()->title($first->first_name),
                    'last_name' => str()->title($first->last_name),
                    'bib' => $first->bib,
                    'total_points' => $pointsPerRace->sum() + $sessionBonus,
                    'races_counted' => $raceRows->count(),
                    'points_per_race' => $pointsPerRace->all(),
                    'session_bonus' => $sessionBonus,
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
                    'uuid' => $first->uuid,
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
