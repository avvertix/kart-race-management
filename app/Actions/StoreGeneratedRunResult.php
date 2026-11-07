<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Store a run result calculated from other results, replacing the one generated before
 * for the same race and run type (and keeping it published if it was).
 */
class StoreGeneratedRunResult
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows  Participant result attributes
     */
    public function __invoke(Race $race, RunType $runType, string $title, Collection $rows): RunResult
    {
        return DB::transaction(function () use ($race, $runType, $title, $rows) {
            $previous = $race->results()
                ->where('run_type', $runType->value)
                ->where('is_generated', true)
                ->first();

            $previous?->participantResults()->delete();
            $previous?->delete();

            $runResult = $race->results()->create([
                'run_type' => $runType->value,
                'title' => $title,
                'file_name' => null,
                'is_generated' => true,
                'published_at' => $previous?->published_at,
            ]);

            foreach ($rows as $row) {
                $runResult->participantResults()->create($row);
            }

            return $runResult;
        });
    }
}
