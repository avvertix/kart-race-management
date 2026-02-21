<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\LinkParticipantResults;
use App\Models\Race;
use Illuminate\Console\Command;

class LinkRaceResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'race:link-results {race : The race UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link participant results for all run results of a given race';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $race = Race::where('uuid', $this->argument('race'))->sole();

        $runResults = $race->results()->get();

        if ($runResults->isEmpty()) {
            $this->info('No run results found for this race.');

            return self::SUCCESS;
        }

        $this->info("Dispatching link jobs for {$runResults->count()} run result(s) of {$race->title}...");

        foreach ($runResults as $runResult) {
            LinkParticipantResults::dispatch($runResult);
            $this->line("  Dispatched for: {$runResult->title}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
