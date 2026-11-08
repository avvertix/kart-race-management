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

    /**
     * Drivers the track can hold: the size of the pre-final grid.
     */
    public const TRACK_CAPACITY = 36;

    public function __construct(
        private CalculateQualifyingRanking $rankQualifying,
        private CalculateHeatAssignment $assignHeats,
        private CalculatePrefinaleGrid $calculatePrefinaleGrid,
    ) {}

    /**
     * Heats assignment of every category, from its qualifying.
     *
     * @param  array<int, mixed>  $requestedHeats  Number of heats keyed by the category index
     * @return Collection<int, array{index: int, category: string, heats: int, ranking: array, assignment: Collection}>
     */
    public function __invoke(Race $race, array $requestedHeats = []): Collection
    {
        $qualifying = $this->participantResultsOf($race, [RunType::QUALIFY_SERIES, RunType::QUALIFY])->groupBy('category');
        $uploadedHeats = $this->participantResultsOf($race, [RunType::HEAT])->pluck('run_result_id')->unique()->count();

        return $qualifying->keys()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (string $category, int $index) use ($qualifying, $uploadedHeats, $requestedHeats) {
                $heats = (int) ($requestedHeats[$index] ?? ($uploadedHeats > 0 ? $uploadedHeats : self::DEFAULT_HEATS));
                $heats = max(1, min(self::MAX_HEATS, $heats));

                $ranking = ($this->rankQualifying)($qualifying->get($category));

                return [
                    'index' => $index,
                    'category' => $category,
                    'heats' => $heats,
                    'ranking' => $ranking,
                    'assignment' => ($this->assignHeats)($ranking, $heats),
                ];
            });
    }

    /**
     * Pre-final starting grid from the uploaded heats. The categories race together in the
     * heats, so the grid is one for the whole race, capped to the capacity of the track.
     *
     * @return array|null Null until heats results are uploaded
     */
    public function prefinale(Race $race): ?array
    {
        $heats = $this->participantResultsOf($race, [RunType::HEAT])->groupBy('run_result_id')->values();

        if ($heats->isEmpty()) {
            return null;
        }

        // Last tie-break between heat winners: qualifying position within their category
        $qualifyingPositions = $this($race)->flatMap(
            fn (array $grid) => $grid['ranking']['ranked']
                ->concat($grid['ranking']['untimed'])
                ->values()
                ->mapWithKeys(fn (ParticipantResult $result, int $position) => [$result->bib => $position + 1])
        );

        return ($this->calculatePrefinaleGrid)($heats, $qualifyingPositions, self::TRACK_CAPACITY);
    }

    /**
     * Uploaded results only: the ones calculated from them are not a source.
     *
     * @param  array<int, RunType>  $runTypes
     * @return Collection<int, ParticipantResult>
     */
    protected function participantResultsOf(Race $race, array $runTypes): Collection
    {
        return ParticipantResult::query()
            ->whereHas('runResult', fn ($query) => $query
                ->where('race_id', $race->getKey())
                ->where('is_generated', false)
                ->whereIn('run_type', array_map(fn (RunType $runType) => $runType->value, $runTypes)))
            ->orderBy('id')
            ->get();
    }
}
