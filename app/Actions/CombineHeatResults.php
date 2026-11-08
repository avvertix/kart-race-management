<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Classify the drivers of the heats as a single race result, following the order of the
 * pre-final starting grid (ACI art. 15.6). Every driver keeps the laps and times of their own heat.
 */
class CombineHeatResults
{
    /**
     * Attributes carried over from the heats to the classification.
     *
     * @var array<int, string>
     */
    public const COPIED_ATTRIBUTES = [
        'participant_id', 'category_id', 'bib', 'status', 'name', 'category', 'racer_hash',
        'is_dnf', 'is_dns', 'is_dq', 'laps', 'total_race_time', 'best_lap_time', 'best_lap_number',
    ];

    public function __construct(
        private BuildRaceHeatGrids $buildGrids,
        private StoreGeneratedRunResult $store,
    ) {}

    public function __invoke(Race $race, RunType $runType): RunResult
    {
        if (! in_array($runType, [RunType::RACE_1, RunType::RACE_2], true)) {
            throw new InvalidArgumentException(__('Heats can only be classified as a race.'));
        }

        $prefinale = $this->buildGrids->prefinale($race);

        if ($prefinale === null) {
            throw new InvalidArgumentException(__('No heats results uploaded.'));
        }

        $uploaded = $race->results()->where('run_type', $runType->value)->where('is_generated', false)->exists();

        if ($uploaded) {
            throw new InvalidArgumentException(__('A :run result is already uploaded for this race.', ['run' => $runType->localizedName()]));
        }

        // Drivers beyond the track capacity did not make the grid but they raced the heats, so they stay classified
        $classified = $prefinale['grid']
            ->concat($prefinale['not_admitted'])
            ->map(fn (array $entry) => $entry['result'])
            ->concat($prefinale['excluded'])
            ->values();

        $positionInCategory = [];

        $rows = $classified->map(function (ParticipantResult $row, int $index) use (&$positionInCategory) {
            $positionInCategory[$row->category] = ($positionInCategory[$row->category] ?? 0) + 1;

            return [
                ...Arr::only($row->getAttributes(), self::COPIED_ATTRIBUTES),
                'position' => (string) ($index + 1),
                'position_in_category' => (string) $positionInCategory[$row->category],
                'gap_from_leader' => '',
                'gap_from_previous' => '',
            ];
        });

        return ($this->store)($race, $runType, __('Heats - intermediate classification'), $rows);
    }
}
