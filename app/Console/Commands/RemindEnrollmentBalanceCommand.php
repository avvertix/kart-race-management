<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Notifications\EnrollmentBalanceDue;
use Illuminate\Console\Command;

class RemindEnrollmentBalanceCommand extends Command
{
    /**
     * Days before the balance due date the User is reminded.
     */
    public const DAYS_BEFORE = 7;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollments:remind-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind once the Users whose enrollment balance is due within a week';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = 0;

        Enrollment::query()
            ->toRemindOfBalance(self::DAYS_BEFORE)
            ->with(['championship', 'user', 'licence', 'licenceRequest', 'payments'])
            ->lazyById()
            ->each(function (Enrollment $enrollment) use (&$count) {
                $enrollment->update(['balance_reminded_at' => now()]);
                $enrollment->user->notify(new EnrollmentBalanceDue($enrollment));

                $count++;
            });

        $this->info("Reminded {$count} enrollments of their balance.");

        return self::SUCCESS;
    }
}
