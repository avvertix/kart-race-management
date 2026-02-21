<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Results\RacerQualifyingResultData;
use App\Data\Results\RacerRaceResultData;
use App\Data\Results\RunResultData;
use App\Models\ResultStatus;
use App\Models\RunType;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Saloon\XmlWrangler\XmlReader;

class ProcessMyLapsResult
{
    /**
     * Process a MyLaps Orbits 5 XML result file and convert it to structured data.
     *
     * @param  string  $filePath  The path to the XML file
     * @param  string|null  $fileName  Optional filename to help determine session type
     */
    public function __invoke(string $filePath, ?string $fileName = null): RunResultData
    {
        $reader = XmlReader::fromFile($filePath);

        $rows = $reader->element('table.row')->collect();

        if ($rows->isEmpty()) {
            throw new InvalidArgumentException('No result rows found in the XML file');
        }

        // Determine session type from filename or default to RACE_1
        $runType = $this->determineRunType($fileName ?? basename($filePath));

        // Extract title from filename (remove session prefix and extension)
        $title = $this->extractTitle($fileName ?? basename($filePath));

        // Group results by category for position calculation
        $categoryResults = $this->groupResultsByCategory($rows);

        // Parse rows based on session type
        if ($runType->isQualify()) {
            $results = $this->parseQualifyingResults($rows, $categoryResults);
        } else {
            $results = $this->parseRaceResults($rows, $categoryResults);
        }

        return new RunResultData($runType, $title, $results);
    }

    /**
     * Parse qualifying session results.
     */
    protected function parseQualifyingResults(Collection $rows, Collection $categoryResults): Collection
    {
        return $rows->map(function ($row, $index) use ($categoryResults) {
            $racerCategory = $row->getAttribute('Class', $row->getAttribute('Classe'));
            $position = $row->getAttribute('Pos');
            $positionInCategory = $row->getAttribute('PIC', $row->getAttribute('Pos'));
            $status = ResultStatus::fromString($position);

            return new RacerQualifyingResultData(
                bib: (int) $row->getAttribute('No.', $row->getAttribute('Num.')),
                status: $status,
                name: $row->getAttribute('Name', $row->getAttribute('Nome')),
                category: $racerCategory,
                position: ResultStatus::matchUnfinishedOrPenalty($position) ? (string) ($index + 1) : $position,
                position_in_category: ResultStatus::matchUnfinishedOrPenalty($positionInCategory)
                    ? (string) ($categoryResults[$racerCategory]->get($row->getAttribute('No.', $row->getAttribute('Num.'))) + 1)
                    : $positionInCategory,
                best_lap_time: $row->getAttribute('Best_Tm', $row->getAttribute('Tempo_Migliore')),
                best_lap_number: $row->getAttribute('In_Lap', $row->getAttribute('Nel_Giro')),
                gap_from_leader: $row->getAttribute('Diff') ?? '',
                gap_from_previous: $row->getAttribute('Gap', $row->getAttribute('Differenza')) ?? '',
                racer_hash: $row->getAttribute('Car_Bike_Reg', $row->getAttribute('Registrazione_del_pilota')),
                second_best_time: $row->getAttribute('Second_Best'),
                second_best_lap_number: $row->getAttribute('Second_Lap'),
                best_speed: $this->parseFloat($row->getAttribute('Best_Speed')),
                second_best_speed: $this->parseFloat($row->getAttribute('Second_Spd')),
                points: $this->parseFloat($row->getAttribute('Points', $row->getAttribute('Punti'))),
                is_dnf: $status->didNotFinish(),
                is_dns: $status->didNotStart(),
                is_dq: $status->disqualified(),
            );
        });
    }

    /**
     * Parse race session results.
     */
    protected function parseRaceResults(Collection $rows, Collection $categoryResults): Collection
    {
        return $rows->map(function ($row, $index) use ($categoryResults) {
            $racerCategory = $row->getAttribute('Class', $row->getAttribute('Classe'));
            $position = $row->getAttribute('Pos');
            $positionInCategory = $row->getAttribute('PIC', $row->getAttribute('Pos'));

            $status = ResultStatus::fromString($position);

            return new RacerRaceResultData(
                bib: (int) $row->getAttribute('No.', $row->getAttribute('Num.')),
                status: $status,
                name: $row->getAttribute('Name', $row->getAttribute('Nome')),
                category: $racerCategory,
                position: ResultStatus::matchUnfinishedOrPenalty($position) ? (string) ($index + 1) : $position,
                position_in_category: ResultStatus::matchUnfinishedOrPenalty($positionInCategory)
                    ? (string) ($categoryResults[$racerCategory]->get($row->getAttribute('No.', $row->getAttribute('Num.'))) + 1)
                    : $positionInCategory,
                laps: (int) $row->getAttribute('Laps', $row->getAttribute('Giri')),
                total_race_time: $row->getAttribute('Total_Tm', $row->getAttribute('Tempo_Totale')),
                gap_from_leader: $row->getAttribute('Diff') ?? '',
                gap_from_previous: $row->getAttribute('Gap', $row->getAttribute('Differenza')) ?? '',
                best_lap_time: $row->getAttribute('Best_Tm', $row->getAttribute('Tempo_Migliore')),
                best_lap_number: $row->getAttribute('In_Lap', $row->getAttribute('Nel_Giro')),
                racer_hash: $row->getAttribute('Car_Bike_Reg', $row->getAttribute('Registrazione_del_pilota')),
                points: $this->parseFloat($row->getAttribute('Points', $row->getAttribute('Punti'))),
                is_dnf: $status->didNotFinish(),
                is_dns: $status->didNotStart(),
                is_dq: $status->disqualified(),
            );
        });
    }

    /**
     * Group results by category and create position index.
     */
    protected function groupResultsByCategory(Collection $rows): Collection
    {
        return $rows->mapToGroups(function ($row) {
            return [$row->getAttribute('Class', $row->getAttribute('Classe')) => $row->getAttribute('No.', $row->getAttribute('Num.'))];
        })->mapWithKeys(function (Collection $group, $key) {
            return [$key => $group->flip()];
        });
    }

    /**
     * Determine run type from filename.
     */
    protected function determineRunType(string $fileName): RunType
    {
        return RunType::fromString($fileName);
    }

    /**
     * Extract title from filename by removing session prefix and file extension.
     */
    protected function extractTitle(string $fileName): string
    {
        // Remove .xml extension
        $title = preg_replace('/\.xml$/i', '', $fileName);

        // Remove common session prefixes (e.g., "3 - GARA 1   RACE 1 - ")
        $title = preg_replace('/^\d+\s*-\s*[^-]+-\s*/', '', $title);

        return mb_trim($title);
    }

    /**
     * Parse float value safely, returning null if not a valid number.
     */
    protected function parseFloat(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
