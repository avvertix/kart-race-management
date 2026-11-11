<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('licence-requests:prune-documents')->daily()->withoutOverlapping();
Schedule::command('notifications:prune-read')->daily()->withoutOverlapping();
Schedule::command('enrollments:remind-balance')->dailyAt('09:00')->timezone(config('races.timezone'))->withoutOverlapping();
