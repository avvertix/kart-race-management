<?php

namespace App\Console\Commands;

use App\Data\Results\RacerRaceResultData;
use App\Data\Results\RacerResultData;
use App\Data\Results\RunResultData;
use App\Data\Results\SessionResultData;
use App\Models\Race;
use App\Models\RaceSession;
use App\Models\ResultStatus;
use App\Models\RunType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Saloon\XmlWrangler\XmlReader;

class RaceResultCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'race:result';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testing command to work with Race results';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $raceUlid = '01hnxdp8pkbmaqb02w0ccb5h7n';

        $race = Race::whereUuid($raceUlid)->sole();


        $disk = Storage::disk('race-results');


        $resultFilePath = $disk->path("{$raceUlid}/GARA 2 - 125 TAG J   TAG S   SUPERTAG  IAME X30 JUNIOR   IAME X30 SENIOR   - Risultati.xml");

        $runType = RunType::fromString('GARA 2 - 125 TAG J   TAG S   SUPERTAG  IAME X30 JUNIOR   IAME X30 SENIOR   - Risultati.xml');

        // TODO: identify session type from file name as files have different structure based on session type

        $reader = XmlReader::fromFile($resultFilePath);

        $rows = $reader->element('table.row')->collect();

        $categoryResults = $rows->mapToGroups(function($row){
            return [$row->getAttribute('Classe') => $row->getAttribute('Num.')];
        })->mapWithKeys(function(Collection $group, $key){
            return [$key => $group->flip()];
        });

        $results = $rows->map(function($row, $index) use ($categoryResults) {

            $racerCategory = $row->getAttribute('Classe');


            return new RacerRaceResultData(
                bib: $row->getAttribute('Num.'),
                status: ResultStatus::fromString($row->getAttribute('Pos')),
                name: $row->getAttribute('Nome'),
                category: $racerCategory,
                position: ResultStatus::matchUnfinishedOrPenalty($row->getAttribute('Pos')) ? $index : $row->getAttribute('Pos'),
                position_in_category: ResultStatus::matchUnfinishedOrPenalty($row->getAttribute('PIC')) ? $categoryResults[$racerCategory]->get($row->getAttribute('Num.'))+1 : $row->getAttribute('PIC'),
                laps:  (int)$row->getAttribute('Giri'),
                total_race_time: $row->getAttribute('Tempo_Totale'),
                gap_from_leader: $row->getAttribute('Diff'),
                gap_from_previous: $row->getAttribute('Differenza'),
                best_lap_time: $row->getAttribute('Tempo_Migliore'),
                best_lap_number: $row->getAttribute('Nel_Giro'),
                racer_hash: $row->getAttribute('Registrazione_della_vettura'),
            );
        });

        $sessionResult = new RunResultData($runType, "60 MINI GR.3   MINI GR.3 U.10   MINI KART - Risultati", $results);

        dump($runType->name);

        $results->each(function($r){
            $this->line("{$r->position} [{$r->status->name}]: {$r->bib} - {$r->category} - {$r->position_in_category}");
        });
    }
}
