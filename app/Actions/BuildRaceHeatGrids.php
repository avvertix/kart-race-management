<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunType;
use Illuminate\Support\Collection;

/**
 * Heats assignment and pre-final grid of every category qualified in a race.
 */
class BuildRaceHeatGrids
{
    public const DEFAULT_HEATS = 2;

    public const MAX_HEATS = 12;

    public function __construct(
        private CalculateQualifyingRanking $rankQualifying,
        private CalculateHeatAssignment $assignHeats,
        private CalculatePrefinaleGrid $calculatePrefinaleGrid,
    ) {}

    /**
     * @param  array<int, mixed>  $requestedHeats  Number of heats keyed by the category index
     * @return Collection<int, array{index: int, category: string, heats: int, ranking: array, assignment: Collection, prefinale: array|null}>
     */
    public function __invoke(Race $race, array $requestedHeats = []): Collection
    {
        $qualifying = $this->participantResultsOf($race, RunType::QUALIFY)->groupBy('category');
        $heatResults = $this->participantResultsOf($race, RunType::HEAT)->groupBy('category');

        return $qualifying->keys()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (string $category, int $index) use ($qualifying, $heatResults, $requestedHeats) {
                $categoryHeatResults = $heatResults->get($category, collect())->groupBy('run_result_id')->values();

                $heats = (int) ($requestedHeats[$index] ?? ($categoryHeatResults->isNotEmpty() ? $categoryHeatResults->count() : self::DEFAULT_HEATS));
                $heats = max(1, min(self::MAX_HEATS, $heats));

                $ranking = ($this->rankQualifying)($qualifying->get($category));

                $qualifyingPositions = $ranking['ranked']
                    ->concat($ranking['untimed'])
                    ->values()
                    ->mapWithKeys(fn (ParticipantResult $result, int $position) => [$result->bib => $position + 1]);

                return [
                    'index' => $index,
                    'category' => $category,
                    'heats' => $heats,
                    'ranking' => $ranking,
                    'assignment' => ($this->assignHeats)($ranking, $heats),
                    'prefinale' => $categoryHeatResults->isNotEmpty()
                        ? ($this->calculatePrefinaleGrid)($categoryHeatResults, $qualifyingPositions)
                        : null,
                ];
            });
    }

    /**
     * @return Collection<int, ParticipantResult>
     */
    protected function participantResultsOf(Race $race, RunType $runType): Collection
    {
        return ParticipantResult::query()
            ->whereHas('runResult', fn ($query) => $query->where('race_id', $race->getKey())->where('run_type', $runType->value))
            ->orderBy('id')
            ->get();
    }
}
