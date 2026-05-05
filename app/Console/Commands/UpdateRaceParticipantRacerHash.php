<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Console\Command;

class UpdateRaceParticipantRacerHash extends Command
{
    protected $signature = 'race:update-racer-hash
                            {race : The ULID of the race}
                            {--dry-run : Print participants that would be updated without making changes}';

    protected $description = 'Update the racer hash for all participants of a given race based on their driver licence';

    public function handle(): int
    {
        $race = Race::where('uuid', $this->argument('race'))->first();

        if (! $race) {
            $this->error("Race [{$this->argument('race')}] not found.");

            return Command::FAILURE;
        }

        $isDryRun = $this->option('dry-run');

        $query = Participant::where('race_id', $race->id);

        $count = $query->count();

        if ($count === 0) {
            $this->line('No participants found for this race.');

            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->line("Dry run — {$count} participant(s) would be updated:");
            $this->newLine();

            $headers = ['ULID', 'Name', 'Driver Licence', 'Current Racer Hash', 'New Racer Hash'];
            $rows = [];

            $query->chunk(100, function ($participants) use (&$rows) {
                foreach ($participants as $participant) {
                    $newHash = mb_substr($participant->driver_licence, 0, 8);
                    $rows[] = [
                        $participant->uuid,
                        "{$participant->first_name} {$participant->last_name}",
                        $participant->driver_licence,
                        $participant->racer_hash ?? '(empty)',
                        $newHash,
                    ];
                }
            });

            $this->table($headers, $rows);

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunk(100, function ($participants) use ($bar) {
            foreach ($participants as $participant) {
                $participant->update(['racer_hash' => mb_substr($participant->driver_licence, 0, 8)]);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Updated racer hash for {$count} participant(s).");

        return Command::SUCCESS;
    }
}
