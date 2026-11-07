<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ParticipantResult;
use App\Support\LapTime;
use Illuminate\Support\Collection;

/**
 * Starting grid of the pre-final from the heats classification (ACI art. 15.6).
 *
 * Heats are sorted by the total time of their winner (then the winner's best lap,
 * then the winner's qualifying position). The grid takes the first of each heat in
 * that order, then the second of each heat and so on. The grid holds at most the
 * capacity of the track (ACI art. 15.4), the following drivers are not admitted.
 */
class CalculatePrefinaleGrid
{
    /**
     * @param  Collection<int, Collection<int, ParticipantResult>>  $heats  Rows of each heat of the category
     * @param  Collection<int, int>  $qualifyingPositions  Overall qualifying position keyed by bib
     * @param  int|null  $capacity  Maximum number of drivers on the grid
     * @return array{
     *     grid: Collection<int, array{position: int, result: ParticipantResult, heat: int, heat_position: int}>,
     *     not_admitted: Collection<int, array{position: int, result: ParticipantResult, heat: int, heat_position: int}>,
     *     heats: Collection<int, array{heat: int, winner: ParticipantResult}>,
     *     excluded: Collection<int, ParticipantResult>,
     * }
     */
    public function __invoke(Collection $heats, Collection $qualifyingPositions, ?int $capacity = null): array
    {
        $excluded = collect();

        $classified = $heats->map(function (Collection $rows) use ($excluded) {
            $rows->filter(fn (ParticipantResult $row) => $row->is_dns || $row->is_dq)
                ->each(fn (ParticipantResult $row) => $excluded->push($row));

            return $rows
                ->reject(fn (ParticipantResult $row) => $row->is_dns || $row->is_dq)
                ->sortBy(fn (ParticipantResult $row) => (int) $row->position)
                ->values();
        })->filter(fn (Collection $rows) => $rows->isNotEmpty());

        $ordered = $classified
            ->sort(fn (Collection $a, Collection $b) => $this->winnerKey($a->first(), $qualifyingPositions) <=> $this->winnerKey($b->first(), $qualifyingPositions))
            ->values();

        $grid = collect();
        $rounds = $ordered->map->count()->max() ?? 0;

        for ($round = 0; $round < $rounds; $round++) {
            foreach ($ordered as $heatIndex => $rows) {
                if (! isset($rows[$round])) {
                    continue;
                }

                $grid->push([
                    'position' => $grid->count() + 1,
                    'result' => $rows[$round],
                    'heat' => $heatIndex + 1,
                    'heat_position' => $round + 1,
                ]);
            }
        }

        return [
            'grid' => $capacity === null ? $grid : $grid->take($capacity)->values(),
            'not_admitted' => $capacity === null ? collect() : $grid->slice($capacity)->values(),
            'heats' => $ordered->map(fn (Collection $rows, int $heatIndex) => [
                'heat' => $heatIndex + 1,
                'winner' => $rows->first(),
            ]),
            'excluded' => $excluded,
        ];
    }

    /**
     * @param  Collection<int, int>  $qualifyingPositions
     * @return array{int, int, int}
     */
    protected function winnerKey(ParticipantResult $winner, Collection $qualifyingPositions): array
    {
        return [
            LapTime::toMilliseconds($winner->total_race_time) ?? PHP_INT_MAX,
            LapTime::toMilliseconds($winner->best_lap_time) ?? PHP_INT_MAX,
            $qualifyingPositions->get($winner->bib, PHP_INT_MAX),
        ];
    }
}
