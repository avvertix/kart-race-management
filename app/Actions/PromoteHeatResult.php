<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ParticipantResult;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Promote a heat to be scored and published as a race (pre-final or final), or remove the promotion.
 *
 * The heat stays a heat, so the heat grids and the pre-final grid keep using it. Changing the
 * promotion clears the points already assigned, as they were calculated for another run type.
 */
class PromoteHeatResult
{
    public function __invoke(RunResult $heat, ?RunType $runType): RunResult
    {
        if (! $heat->run_type->isHeat()) {
            throw new InvalidArgumentException(__('Only heats can be promoted.'));
        }

        if ($runType !== null && ! in_array($runType, [RunType::RACE_1, RunType::RACE_2], true)) {
            throw new InvalidArgumentException(__('Heats can only be classified as a race.'));
        }

        if ($heat->promoted_to === $runType) {
            return $heat;
        }

        if ($runType !== null) {
            $this->ensureCategoriesAreNotScoredElsewhere($heat, $runType);
        }

        return DB::transaction(function () use ($heat, $runType) {
            $heat->participantResults()->update(['points' => null]);

            $heat->update([
                'promoted_to' => $runType,
                'published_at' => $runType === null ? null : $heat->published_at,
            ]);

            return $heat;
        });
    }

    /**
     * A category of the heat already in a result of the same race (uploaded or classified from
     * the heats) would be scored twice. Other categories may legitimately have their own race.
     */
    private function ensureCategoriesAreNotScoredElsewhere(RunResult $heat, RunType $runType): void
    {
        $duplicated = ParticipantResult::query()
            ->whereHas('runResult', fn ($query) => $query
                ->where('race_id', $heat->race_id)
                ->where('run_type', $runType->value))
            ->whereIn('category', $heat->participantResults()->distinct()->pluck('category')->all())
            ->distinct()
            ->pluck('category');

        if ($duplicated->isNotEmpty()) {
            throw new InvalidArgumentException(__('Already in a :run result: :categories.', [
                'run' => $runType->localizedName(),
                'categories' => $duplicated->join(', '),
            ]));
        }
    }
}
