<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use App\Support\LapTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Combine the qualifying series (groups) of a race in a single qualifying, ranked
 * as the official qualifying classification (ACI art. 15.3), so that the points
 * and the day award do not depend on how the organizer split the drivers.
 */
class CombineQualifyingSeries
{
    /**
     * Attributes carried over from the series to the combined qualifying.
     *
     * @var array<int, string>
     */
    public const COPIED_ATTRIBUTES = [
        'participant_id', 'category_id', 'bib', 'status', 'name', 'category', 'racer_hash',
        'is_dnf', 'is_dns', 'is_dq', 'best_lap_time', 'best_lap_number', 'second_best_time',
        'second_best_lap_number', 'best_speed', 'second_best_speed',
    ];

    public function __construct(
        private CalculateQualifyingRanking $rankQualifying,
        private StoreGeneratedRunResult $store,
    ) {}

    public function __invoke(Race $race): RunResult
    {
        $series = ParticipantResult::query()
            ->whereHas('runResult', fn ($query) => $query
                ->where('race_id', $race->getKey())
                ->where('run_type', RunType::QUALIFY_SERIES->value)
                ->where('is_generated', false))
            ->orderBy('id')
            ->get();

        if ($series->isEmpty()) {
            throw new InvalidArgumentException(__('No qualifying series uploaded.'));
        }

        $this->ensureCategoriesAreNotQualifiedElsewhere($race, $series);

        $position = 0;

        $rows = $series->groupBy('category')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->flatMap(function (Collection $categoryRows) use (&$position) {
                $ranking = ($this->rankQualifying)($categoryRows);

                $ordered = $ranking['ranked']->concat($ranking['untimed'])->concat($ranking['excluded'])->values();

                $leaderTime = LapTime::toMilliseconds($ordered->first()?->best_lap_time);

                return $ordered->map(function (ParticipantResult $row, int $index) use ($ordered, $leaderTime, &$position) {
                    $time = LapTime::toMilliseconds($row->best_lap_time);
                    $previousTime = $index > 0 ? LapTime::toMilliseconds($ordered[$index - 1]->best_lap_time) : null;

                    return [
                        ...Arr::only($row->getAttributes(), self::COPIED_ATTRIBUTES),
                        'position' => (string) ++$position,
                        'position_in_category' => (string) ($index + 1),
                        'gap_from_leader' => $index > 0 && $time !== null && $leaderTime !== null ? $this->formatGap($time - $leaderTime) : '',
                        'gap_from_previous' => $index > 0 && $time !== null && $previousTime !== null ? $this->formatGap($time - $previousTime) : '',
                    ];
                });
            })
            ->values();

        return ($this->store)($race, RunType::QUALIFY, __('Qualifying - combined series'), $rows);
    }

    /**
     * A category already qualified in an uploaded qualifying would be scored twice.
     *
     * @param  Collection<int, ParticipantResult>  $series
     */
    private function ensureCategoriesAreNotQualifiedElsewhere(Race $race, Collection $series): void
    {
        $duplicated = ParticipantResult::query()
            ->whereHas('runResult', fn ($query) => $query
                ->where('race_id', $race->getKey())
                ->where('run_type', RunType::QUALIFY->value)
                ->where('is_generated', false))
            ->whereIn('category', $series->pluck('category')->unique()->all())
            ->distinct()
            ->pluck('category');

        if ($duplicated->isNotEmpty()) {
            throw new InvalidArgumentException(__('Already in an uploaded qualifying: :categories.', ['categories' => $duplicated->join(', ')]));
        }
    }

    private function formatGap(int $milliseconds): string
    {
        return number_format($milliseconds / 1000, 3, '.', '');
    }
}
