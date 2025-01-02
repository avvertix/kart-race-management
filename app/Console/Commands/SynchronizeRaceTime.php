<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Race;
use Illuminate\Console\Command;

class SynchronizeRaceTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'races:sync-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize the race times after configuration changes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('Synchonizing race times...');

        $query = Race::where('event_start_at', '>=', now()->startOfDay());

        $bar = $this->output->createProgressBar($query->count());

        $bar->start();

        $query->chunk(50, function ($races) use ($bar) {

            foreach ($races as $race) {
                $start_date = $race->event_start_at->setTimezone(config('races.timezone'))->setTimeFromTimeString(config('races.start_time'));
                $end_date = $race->event_end_at->setTimezone(config('races.timezone'))->setTimeFromTimeString(config('races.end_time'));

                $utc_start_date = $start_date->setTimezone(config('app.timezone'));
                $utc_end_date = $end_date->setTimezone(config('app.timezone'));

                $race->update([
                    'event_start_at' => $utc_start_date,
                    'event_end_at' => $utc_end_date,
                    'registration_opens_at' => $utc_start_date->copy()->subHours(config('races.registration.opens')),
                    'registration_closes_at' => $utc_start_date->copy()->subHours(config('races.registration.closes')),
                ]);

                $bar->advance();
            }

        });

        $bar->finish();

        return Command::SUCCESS;
    }
}
