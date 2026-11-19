<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneReadNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:prune-read';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the notifications read more than 12 months ago';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = DatabaseNotification::query()->where('read_at', '<', now()->subYear())->delete();

        $this->info("Deleted {$deleted} read notifications.");

        return self::SUCCESS;
    }
}
