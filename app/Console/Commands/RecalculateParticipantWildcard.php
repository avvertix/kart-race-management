<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Wildcard\RecalculateWildcards;
use App\Models\Race;
use Illuminate\Console\Command;

class RecalculateParticipantWildcard extends Command
{
    protected $signature = 'participants:recalculate-wildcard
                            {race : The UUID of the race}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Recalculate and optionally save wildcard status for all participants in a race';

    public function handle(RecalculateWildcards $recalculateWildcards): int
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

        $results = $recalculateWildcards->forRace($race, save: ! $isDryRun);

        if ($results->isEmpty()) {
            $this->warn('No participants found for this race.');

            return Command::SUCCESS;
        }

        $rows = [];
        $changedCount = 0;

        foreach ($results as $result) {
            if ($result['changed']) {
                $changedCount++;
            }

            $rows[] = [
                $result['participant']->bib,
                $result['participant']->full_name,
                $this->formatWildcard($result['current']),
                $this->formatWildcard($result['recalculated']),
                $result['changed'] ? '<comment>yes</comment>' : 'no',
                $result['participant']->id,
            ];
        }

        $this->table(
            ['BIB', 'Participant', 'Current wildcard', 'Recalculated wildcard', 'Changed', 'Participation id'],
            $rows
        );

        $this->line("{$changedCount} of {$results->count()} participant(s) have a different wildcard status.");

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
