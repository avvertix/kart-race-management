<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Participant;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class NotifyParticipantUpdate extends Command
{
    protected $signature = 'participants:notify-update
                            {participants?* : Participant IDs to notify}
                            {--file= : Path to a JSON file containing participant UUIDs}';

    protected $description = 'Send an update notification to participants by UUID or from a JSON file';

    public function handle(): int
    {
        $uuids = $this->collectIds();

        if (empty($uuids)) {

            throw new Exception('No participant UUIDs provided. Pass IDs as arguments or use --file.');
        }

        $participants = Participant::whereIn('id', array_unique($uuids))->get();

        $missing = array_diff($uuids, $participants->pluck('id')->all());

        foreach ($missing as $uuid) {
            $this->warn("Participant [{$uuid}] not found, skipping.");
        }

        if ($participants->isEmpty()) {
            throw new Exception('No matching participants found.');
        }

        $bar = $this->output->createProgressBar($participants->count());
        $bar->start();

        foreach ($participants as $participant) {
            $participant->sendUpdateParticipantNotification();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Notification sent to {$participants->count()} participant(s).");

        return Command::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function collectIds(): array
    {
        $uuids = (array) $this->argument('participants');

        if ($file = $this->option('file')) {
            if (! Storage::exists($file)) {
                throw new Exception("File [{$file}] not found in storage.");
            }

            $decoded = Storage::json($file);

            if (! is_array($decoded)) {
                throw new Exception("File [{$file}] does not contain a valid JSON array.");
            }

            $uuids = array_merge($uuids, $decoded);
        }

        return array_values(array_filter($uuids));
    }
}
