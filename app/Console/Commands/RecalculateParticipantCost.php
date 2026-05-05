<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RecalculateParticipantCost extends Command
{
    protected $signature = 'participants:recalculate-cost
                            {race : The UUID of the race}
                            {--dry-run : Preview changes without saving}
                            {--notify-participant : Send update notification to participants whose cost changed}
                            {--json : Output a JSON array of participant UUIDs whose cost changed}';

    protected $description = 'Recalculate and optionally save participation cost for all participants in a race';

    public function handle(): int
    {
        $race = Race::where('uuid', $this->argument('race'))->first();

        if (! $race) {
            $this->error("Race [{$this->argument('race')}] not found.");

            return Command::FAILURE;
        }

        $isDryRun = $this->option('dry-run');

        $this->line($isDryRun
            ? 'Dry-run mode — no changes will be saved.'
            : "Recalculating costs for race: {$race->title}"
        );

        $participants = Participant::where('race_id', $race->id)
            ->with(['racingCategory', 'championship', 'bonuses'])
            ->get();

        if ($participants->isEmpty()) {
            $this->warn('No participants found for this race.');

            return Command::SUCCESS;
        }

        $rows = [];
        $changedCount = 0;
        $changedIds = [];

        foreach ($participants as $participant) {
            $savedCost = $participant->cost?->total() ?? null;
            $newCost = $participant->calculateParticipationCost();
            $newTotal = $newCost->total();

            $changed = $savedCost !== $newTotal;

            if ($changed) {
                $changedCount++;
                $changedIds[] = $participant->uuid;
            }

            $rows[] = [
                $participant->bib,
                $participant->full_name,
                $savedCost !== null ? number_format($savedCost / 100, 2) : '—',
                number_format($newTotal / 100, 2),
                $changed ? '<comment>yes</comment>' : 'no',
                $participant->payment_confirmed_at?->toDateString(),
                $participant->id,
            ];

            if (! $isDryRun && $changed) {
                $participant->cost = $newCost;
                $participant->save();

                if ($this->option('notify-participant')) {
                    $participant->sendUpdateParticipantNotification();
                }
            }
        }

        $this->table(
            ['BIB', 'Participant', 'Saved cost', 'Recalculated cost', 'Changed', 'Payment confirmed', 'Participation id'],
            $rows
        );

        $this->line("{$changedCount} of {$participants->count()} participant(s) have a different cost.");

        if ($isDryRun && $changedCount > 0) {
            $this->warn('Run without --dry-run to apply the changes.');
        }

        if ($this->option('json')) {
            Storage::put('changed-participants.json', json_encode($changedIds));
        }

        return Command::SUCCESS;
    }
}
