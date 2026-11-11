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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use stdClass;

class CalculateAwardRanking
{
    /**
     * Tie-breaks applied, in order, to day award drivers with the same points.
     *
     * @var array<int, string>
     */
    public const DAY_AWARD_TIE_BREAKERS = ['session_bonus', 'race_1_best_lap_time', 'race_2_best_lap_time', 'qualifying_best_lap_time'];

    /**
     * Sessions considered when ranking a day award.
     *
     * @var array<int, RunType>
     */
    private const DAY_AWARD_RUN_TYPES = [RunType::QUALIFY, RunType::RACE_1, RunType::RACE_2];

    /**
     * Calculate the ranking for a given award.
     *
     * Each entry has `participant_id`, `racer_hash`, `first_name`, `last_name`, `bib`, `total_points`, `races_counted` and
     * `points_per_race`. Day awards also include the `session_bonus` already added to `total_points`, the lap
     * times used as tie-breaks and, in `tie_breaks`, how ties with adjacent drivers were resolved.
     *
     * @return Collection<int, array<string, mixed>>
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
     *     day_ranking: ?array,
     *     entries_by_race: Collection<int, Collection<int, array>>,
     * }
     */
    public function breakdown(ChampionshipAward $award, string $racerHash, bool $publishedOnly = false): array
    {
        if ($award->isDayAward()) {
            // Ties depend on the other drivers, so the whole ranking is needed
            $ranking = $this($award, $publishedOnly);
            $position = $ranking->search(fn (array $entry) => $entry['racer_hash'] === $racerHash);
            $summary = $position === false ? null : [...$ranking->get($position), 'position' => $position + 1];
        } else {
            $summaryQuery = $this->buildAwardQuery($award, $publishedOnly)
                ->where('participants.racer_hash', $racerHash);

            $summary = $this->rank($award, $this->fetchPerRacePoints($summaryQuery))->first();
        }

        if ($summary === null) {
            return [
                'total_points' => 0.0,
                'races_counted' => 0,
                'counted_race_ids' => [],
                'points_per_race' => [],
                'session_bonus' => null,
                'day_ranking' => null,
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
            'day_ranking' => $award->isDayAward() ? Arr::only($summary, ['position', ...self::DAY_AWARD_TIE_BREAKERS, 'tie_breaks']) : null,
            'entries_by_race' => $this->fetchRunDetails($entriesQuery),
        ];
    }

    /**
     * Build the query selecting the participant results in scope for the award: its races,
     * categories and wildcard filter. Day awards only consider qualifying and race sessions.
     *
     * @return Builder<ParticipantResult>
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

    /**
     * @param  Collection<int, stdClass>  $perRacePoints
     * @return Collection<int, array<string, mixed>>
     */
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
                participant_results.position_in_category as position_in_category,
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
                'position_in_category' => $row->position_in_category,
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
     * For day awards each row also carries the smallest number of entries (all categories,
     * DNS and DNF included) among the sessions the participant raced in, and the best lap
     * times of race 1, race 2 and qualifying, all used to rank and break ties.
     *
     * When a race result is generated from the heats, its size (the sum of the heats) is the
     * session size of that race, so a smaller final does not lower the bonus.
     */
    private function fetchPerRacePoints(Builder $query, bool $forDayAward = false): Collection
    {
        if ($forDayAward) {
            $sessionSizes = ParticipantResult::query()
                ->selectRaw('run_result_id, COUNT(*) as session_size')
                ->groupBy('run_result_id');

            $query
                ->joinSub($sessionSizes, 'session_sizes', 'session_sizes.run_result_id', '=', 'participant_results.run_result_id')
                ->selectRaw('MIN(session_sizes.session_size) as session_size')
                ->selectRaw('MAX(CASE WHEN run_results.is_generated = 1 AND run_results.run_type IN (?, ?) THEN session_sizes.session_size END) as heats_session_size', [RunType::RACE_1->value, RunType::RACE_2->value])
                ->selectRaw('MAX(CASE WHEN run_results.run_type = ? AND participant_results.is_dns = 0 AND participant_results.is_dnf = 0 THEN participant_results.best_lap_time END) as race_1_best_lap_time', [RunType::RACE_1->value])
                ->selectRaw('MAX(CASE WHEN run_results.run_type = ? AND participant_results.is_dns = 0 AND participant_results.is_dnf = 0 THEN participant_results.best_lap_time END) as race_2_best_lap_time', [RunType::RACE_2->value])
                ->selectRaw('MAX(CASE WHEN run_results.run_type = ? THEN participant_results.best_lap_time END) as qualifying_best_lap_time', [RunType::QUALIFY->value]);
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
     *
     * Ties are broken by the larger session bonus (the driver in the busiest group wins),
     * then by the faster best lap in race 1, in race 2 and finally in qualifying.
     * Race laps only count when the driver finished that race (no DNS or DNF);
     * a missing or not counted lap time ranks last.
     *
     * @param  Collection<int, stdClass>  $perRacePoints
     * @return Collection<int, array<string, mixed>>
     */
    private function rankByDay(Collection $perRacePoints): Collection
    {
        $ranking = $perRacePoints
            ->groupBy('racer_hash')
            ->map(fn (Collection $raceRows) => $this->dayEntry($raceRows))
            ->sort(fn (array $a, array $b) => $this->dayRankingKey($a) <=> $this->dayRankingKey($b))
            ->values();

        return $ranking->map(fn (array $entry, int $index) => $this->withTieBreaks($entry, $ranking->get($index - 1), $ranking->get($index + 1)));
    }

    /**
     * One driver's day award entry from their rows, one per race.
     *
     * @param  Collection<int, stdClass>  $raceRows
     * @return array<string, mixed>
     */
    private function dayEntry(Collection $raceRows): array
    {
        $first = $raceRows->first();
        $pointsPerRace = $raceRows->mapWithKeys(fn ($row) => [$row->race_id => (float) $row->race_points]);
        $sessionBonus = (int) $raceRows
            ->map(fn ($row) => $row->heats_session_size ?? $row->session_size)
            ->min();

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
            'race_1_best_lap_time' => $raceRows->pluck('race_1_best_lap_time')->filter()->first(),
            'race_2_best_lap_time' => $raceRows->pluck('race_2_best_lap_time')->filter()->first(),
            'qualifying_best_lap_time' => $raceRows->pluck('qualifying_best_lap_time')->filter()->first(),
        ];
    }

    /**
     * The entry with how its ties with the drivers above and below were resolved.
     *
     * @param  array<string, mixed>  $entry
     * @param  ?array<string, mixed>  $above
     * @param  ?array<string, mixed>  $below
     * @return array<string, mixed>
     */
    private function withTieBreaks(array $entry, ?array $above, ?array $below): array
    {
        return [
            ...$entry,
            'tie_breaks' => array_values(array_filter([
                $this->resolveTieBreak($entry, $above, 'above'),
                $this->resolveTieBreak($entry, $below, 'below'),
            ])),
        ];
    }

    /**
     * The values a day award ranking is sorted by, in order: points, then the tie-breaks.
     *
     * @param  array<string, mixed>  $entry
     * @return array<int, float|int>
     */
    private function dayRankingKey(array $entry): array
    {
        return [
            -$entry['total_points'],
            ...array_map(
                fn (string $criterion) => $criterion === 'session_bonus' ? -$entry['session_bonus'] : $this->lapTimeInSeconds($entry[$criterion]),
                self::DAY_AWARD_TIE_BREAKERS,
            ),
        ];
    }

    /**
     * Explain how a tie on points with an adjacent driver in the ranking was resolved:
     * the first tie-break whose values differ, or null as criterion when all are equal.
     *
     * @param  array<string, mixed>  $entry
     * @param  ?array<string, mixed>  $other
     * @return array{position: string, first_name: string, last_name: string, bib: int, criterion: ?string, value: int|string|null, other_value: int|string|null}|null
     */
    private function resolveTieBreak(array $entry, ?array $other, string $position): ?array
    {
        if ($other === null || $other['total_points'] !== $entry['total_points']) {
            return null;
        }

        $entryKey = $this->dayRankingKey($entry);
        $otherKey = $this->dayRankingKey($other);

        $criterion = collect(self::DAY_AWARD_TIE_BREAKERS)
            ->first(fn (string $criterion, int $index) => $entryKey[$index + 1] !== $otherKey[$index + 1]);

        return [
            'position' => $position,
            'first_name' => $other['first_name'],
            'last_name' => $other['last_name'],
            'bib' => $other['bib'],
            'criterion' => $criterion,
            'value' => $criterion ? $entry[$criterion] : null,
            'other_value' => $criterion ? $other[$criterion] : null,
        ];
    }

    /**
     * Convert a lap time as exported by MyLaps (e.g. "56.063" or "1:02.345") to seconds.
     * A missing or unreadable time is considered infinitely slow.
     */
    private function lapTimeInSeconds(?string $lapTime): float
    {
        if (blank($lapTime) || ! preg_match('/^(?:(\d+):)?(\d+(?:\.\d+)?)$/', mb_trim($lapTime), $matches)) {
            return INF;
        }

        return ((int) $matches[1]) * 60 + (float) $matches[2];
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
