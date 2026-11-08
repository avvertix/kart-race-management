<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ParticipantResult;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Split a category into heats following the qualifying classification (ACI art. 15.4).
 *
 * With three heats the 1st goes to heat 1, the 2nd to heat 2, the 3rd to heat 3,
 * the 4th to heat 1 and so on. Drivers without a qualifying time take the last
 * positions, their order is decided by draw so it is left to the race director.
 */
class CalculateHeatAssignment
{
    /**
     * @param  array{ranked: Collection<int, ParticipantResult>, untimed: Collection<int, ParticipantResult>}  $ranking
     * @return Collection<int, Collection<int, array{result: ParticipantResult, overall: int, grid: int, drawn: bool}>> Keyed by heat number, starting from 1
     */
    public function __invoke(array $ranking, int $heats): Collection
    {
        if ($heats < 1) {
            throw new InvalidArgumentException('At least one heat is required.');
        }

        $assignment = collect(range(1, $heats))->mapWithKeys(fn (int $heat) => [$heat => collect()]);

        $entries = $ranking['ranked']->map(fn (ParticipantResult $result) => [$result, false])
            ->concat($ranking['untimed']->map(fn (ParticipantResult $result) => [$result, true]));

        foreach ($entries->values() as $index => [$result, $drawn]) {
            $heat = ($index % $heats) + 1;

            $assignment[$heat]->push([
                'result' => $result,
                'overall' => $index + 1,
                'grid' => $assignment[$heat]->count() + 1,
                'drawn' => $drawn,
            ]);
        }

        return $assignment;
    }
}
