<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ParticipantResult;
use App\Support\LapTime;
use Illuminate\Support\Collection;

/**
 * Overall qualifying classification of a category (ACI art. 15.3).
 *
 * When the category qualified in more than one series the classification is
 * based on the lap times if the two best times differ by 1% or less, otherwise
 * on the position within each series and then on the lap time.
 */
class CalculateQualifyingRanking
{
    public const SERIES_TIME_THRESHOLD_PERCENT = 1.0;

    /**
     * @param  Collection<int, ParticipantResult>  $results  Qualifying rows of a single category, from all the series
     * @return array{
     *     ranked: Collection<int, ParticipantResult>,
     *     untimed: Collection<int, ParticipantResult>,
     *     excluded: Collection<int, ParticipantResult>,
     *     method: 'single'|'time'|'position',
     *     gap_percent: float|null,
     *     series: int,
     * }
     */
    public function __invoke(Collection $results): array
    {
        $excluded = $results->filter(fn (ParticipantResult $result) => $result->is_dq)->values();

        $candidates = $results->reject(fn (ParticipantResult $result) => $result->is_dq);

        [$timed, $untimed] = $candidates->partition(
            fn (ParticipantResult $result) => ! $result->is_dns && LapTime::toMilliseconds($result->best_lap_time) !== null
        );

        $series = $timed
            ->groupBy('run_result_id')
            ->map(fn (Collection $rows) => $rows->sort($this->compareByTime(...))->values()->each(
                fn (ParticipantResult $row, int $index) => $row->setAttribute('series_position', $index + 1)
            ))
            ->values();

        $method = 'single';
        $gapPercent = null;

        if ($series->count() > 1) {
            $leaders = $series
                ->map(fn (Collection $rows) => LapTime::toMilliseconds($rows->first()->best_lap_time))
                ->sort()
                ->values();

            $gapPercent = (($leaders[1] - $leaders[0]) / $leaders[0]) * 100;
            $method = $gapPercent > self::SERIES_TIME_THRESHOLD_PERCENT ? 'position' : 'time';
        }

        $ranked = $series->collapse()->sort(
            fn (ParticipantResult $a, ParticipantResult $b) => $method === 'position'
                ? [$a->series_position, ...$this->timeKey($a)] <=> [$b->series_position, ...$this->timeKey($b)]
                : $this->compareByTime($a, $b)
        )->values();

        return [
            'ranked' => $ranked,
            'untimed' => $untimed->sortBy('bib')->values(),
            'excluded' => $excluded,
            'method' => $method,
            'gap_percent' => $gapPercent,
            'series' => $series->count(),
        ];
    }

    /**
     * Best lap, then second best lap (ex aequo rule). The third and following
     * laps are not part of the result files.
     */
    protected function compareByTime(ParticipantResult $a, ParticipantResult $b): int
    {
        return $this->timeKey($a) <=> $this->timeKey($b);
    }

    /**
     * @return array{int, int}
     */
    protected function timeKey(ParticipantResult $result): array
    {
        return [
            LapTime::toMilliseconds($result->best_lap_time) ?? PHP_INT_MAX,
            LapTime::toMilliseconds($result->second_best_time) ?? PHP_INT_MAX,
        ];
    }
}
