<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ItalianRegion;
use App\Models\ItalianPostalCode;
use Illuminate\Console\Command;

class ImportItalianPostalCodes extends Command
{
    // Dataset source gi_comuni_cap.json from
    // https://www.gardainformatica.it/database-comuni-italiani

    protected $signature = 'import-postal-codes
                            {file : Path to the JSON file with postal codes}';

    protected $description = 'Import Italian postal codes (CAP) from an JSON dataset';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return Command::FAILURE;
        }

        $json = file_get_contents($path);
        $records = json_decode($json, true);

        if (! is_array($records)) {
            $this->error('Invalid JSON: expected a top-level array of records.');

            return Command::FAILURE;
        }

        $this->info('Mapping records by CAP (deduplicating)...');

        $byCap = [];
        $skipped = 0;

        foreach ($records as $record) {
            $cap = mb_trim($record['cap'] ?? '');
            $provinceCode = mb_strtoupper(mb_trim($record['sigla_provincia'] ?? ''));
            $regionName = mb_trim($record['denominazione_regione'] ?? '');

            if ($cap === '' || $provinceCode === '') {
                $skipped++;

                continue;
            }

            $region = ItalianRegion::fromDatasetRegionName($regionName);

            if ($region === null) {
                $this->warn("Unknown region name \"{$regionName}\" for CAP {$cap} — skipping.");
                $skipped++;

                continue;
            }

            $byCap[$cap] = [
                'cap' => $cap,
                'province_code' => $provinceCode,
                'province' => mb_trim($record['denominazione_provincia'] ?? ''),
                'municipality' => mb_trim($record['denominazione_ita'] ?? ''),
                'region' => $region->value,
            ];
        }

        $rows = array_values($byCap);
        $total = count($rows);

        if ($total === 0) {
            $this->warn('No valid records found.');

            return Command::SUCCESS;
        }

        $this->info("Importing {$total} unique CAP codes ({$skipped} records skipped)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($rows, 500) as $chunk) {
            ItalianPostalCode::upsert($chunk, ['cap'], ['province_code', 'province', 'municipality', 'region']);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$total} CAP codes imported.");

        return Command::SUCCESS;
    }
}
