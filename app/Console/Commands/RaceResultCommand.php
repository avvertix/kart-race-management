<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ProcessMyLapsResult;
use App\Data\Results\RacerRaceResultData;
use App\Data\Results\RunResultData;
use App\Models\Race;
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
    protected $signature = 'race:result {file? : The fixture file name to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testing command to work with Race results';

    /**
     * Execute the console command.
     */
    public function handle(ProcessMyLapsResult $processMyLapsResult)
    {

        $raceUlid = '01hnxdp8pkbmaqb02w0ccb5h7n';

        // $race = Race::whereUuid($raceUlid)->sole();

        // $disk = Storage::disk('race-results');

        // $resultFilePath = $disk->path("{$raceUlid}/GARA 2 - 125 TAG J   TAG S   SUPERTAG  IAME X30 JUNIOR   IAME X30 SENIOR   - Risultati.xml");

        // $runType = RunType::fromString('GARA 2 - 125 TAG J   TAG S   SUPERTAG  IAME X30 JUNIOR   IAME X30 SENIOR   - Risultati.xml');

        // // TODO: identify session type from file name as files have different structure based on session type

        // $reader = XmlReader::fromFile($resultFilePath);

        // $rows = $reader->element('table.row')->collect();

        // // Assuming order in source file is respect position

        // $categoryResults = $rows->mapToGroups(function($row){
        //     return [$row->getAttribute('Classe') => $row->getAttribute('Num.')];
        // })->mapWithKeys(function(Collection $group, $key){
        //     return [$key => $group->flip()];
        // });

        // $results = $rows->map(function($row, $index) use ($categoryResults) {

        //     $racerCategory = $row->getAttribute('Classe');

        //     return new RacerRaceResultData(
        //         bib: $row->getAttribute('Num.'),
        //         status: ResultStatus::fromString($row->getAttribute('Pos')),
        //         name: $row->getAttribute('Nome'),
        //         category: $racerCategory,
        //         position: ResultStatus::matchUnfinishedOrPenalty($row->getAttribute('Pos')) ? $index : $row->getAttribute('Pos'),
        //         position_in_category: ResultStatus::matchUnfinishedOrPenalty($row->getAttribute('PIC')) ? $categoryResults[$racerCategory]->get($row->getAttribute('Num.'))+1 : $row->getAttribute('PIC'),
        //         laps:  (int)$row->getAttribute('Giri'),
        //         total_race_time: $row->getAttribute('Tempo_Totale'),
        //         gap_from_leader: $row->getAttribute('Diff'),
        //         gap_from_previous: $row->getAttribute('Differenza'),
        //         best_lap_time: $row->getAttribute('Tempo_Migliore'),
        //         best_lap_number: $row->getAttribute('Nel_Giro'),
        //         racer_hash: $row->getAttribute('Registrazione_della_vettura'),
        //     );
        // });

        // $sessionResult = new RunResultData($runType, "60 MINI GR.3   MINI GR.3 U.10   MINI KART - Risultati", $results);

        // dump($runType->name);

        // $results->each(function($r){
        //     $this->line("{$r->position} [{$r->status->name}]: {$r->bib} - {$r->category} - {$r->position_in_category}");
        // });

        // Get the fixture file to test
        $fileName = $this->argument('file') ?? 'race-2-results.xml';

        $filePath = base_path("tests/fixtures/{$fileName}");

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->info('Available fixtures:');

            $fixtures = glob(base_path('tests/fixtures/*.xml'));
            foreach ($fixtures as $fixture) {
                $this->line('  - '.basename($fixture));
            }

            return 1;
        }

        $this->info("Processing: {$fileName}");
        $this->newLine();

        // Process the XML file using the Action
        $result = $processMyLapsResult($filePath, $fileName);

        // Display the parsed results
        $this->info("Session Type: {$result->session->name}");
        $this->info("Title: {$result->title}");
        $this->info("Total Results: {$result->results->count()}");
        $this->newLine();

        // Display results in a table
        $headers = ['Pos', 'PIC', 'Bib', 'Name', 'Category', 'Status'];

        $rows = $result->results->map(function ($r) {
            return [
                $r->position,
                $r->position_in_category,
                $r->bib,
                $r->name,
                $r->category,
                $r->status->name,
            ];
        });

        $this->table($headers, $rows);

        return 0;
    }
}
