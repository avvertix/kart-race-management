<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Console\Command;

class RecalculateParticipantWildcard extends Command
{
    protected $signature = 'participants:recalculate-wildcard
                            {race : The UUID of the race}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Recalculate and optionally save wildcard status for all participants in a race';

    public function handle(): int
    {
        $race = Race::where('uuid', $this->argument('race'))->first();

        if (! $race) {
            $this->error("Race [{$this->argument('race')}] not found.");

            return Command::FAILURE;
        }

        $championship = $race->championship;

        if (! $championship->wildcard?->enabled) {
            $this->warn('Wildcard is not enabled for this championship.');

            return Command::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');

        $this->line($isDryRun
            ? 'Dry-run mode — no changes will be saved.'
            : "Recalculating wildcard status for race: {$race->title}"
        );

        $participants = Participant::where('race_id', $race->id)
            ->with(['racingCategory', 'championship', 'bonuses'])
            ->get();

        if ($participants->isEmpty()) {
            $this->warn('No participants found for this race.');

            return Command::SUCCESS;
        }

        $evaluate = $championship->wildcard->strategy->resolve();

        $rows = [];
        $changedCount = 0;

        foreach ($participants as $participant) {
            $currentWildcard = $participant->wildcard;
            $newWildcard = $evaluate($participant, $race);

            $changed = $currentWildcard !== $newWildcard;

            if ($changed) {
                $changedCount++;
            }

            $rows[] = [
                $participant->bib,
                $participant->full_name,
                $this->formatWildcard($currentWildcard),
                $this->formatWildcard($newWildcard),
                $changed ? '<comment>yes</comment>' : 'no',
                $participant->id,
            ];

            if (! $isDryRun && $changed) {
                $participant->wildcard = $newWildcard;
                $participant->save();
            }
        }

        $this->table(
            ['BIB', 'Participant', 'Current wildcard', 'Recalculated wildcard', 'Changed', 'Participation id'],
            $rows
        );

        $this->line("{$changedCount} of {$participants->count()} participant(s) have a different wildcard status.");

        if ($isDryRun && $changedCount > 0) {
            $this->warn('Run without --dry-run to apply the changes.');
        }

        return Command::SUCCESS;
    }

    private function formatWildcard(?bool $value): string
    {
        if ($value === null) {
            return '—';
        }

        return $value ? 'yes' : 'no';
    }
}
