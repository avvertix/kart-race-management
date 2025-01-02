<?php

declare(strict_types=1);

namespace App\ActivityLog;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;

class EncryptSensibleParticipantData implements LoggablePipe
{
    public function __construct() {}

    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        if (Arr::has($event->changes, ['attributes.driver.email']) && Str::contains(Arr::get($event->changes, 'attributes.driver.email'), '@')) {

            Arr::set($event->changes, 'attributes.driver.email', Crypt::encryptString(Arr::get($event->changes, 'attributes.driver.email')));
            Arr::set($event->changes, 'old.driver.email', Crypt::encryptString(Arr::get($event->changes, 'old.driver.email')));

        }

        if (Arr::has($event->changes, ['attributes.competitor.email']) && Str::contains(Arr::get($event->changes, 'attributes.competitor.email'), '@')) {

            Arr::set($event->changes, 'attributes.competitor.email', Crypt::encryptString(Arr::get($event->changes, 'attributes.competitor.email')));
            Arr::set($event->changes, 'old.competitor.email', Crypt::encryptString(Arr::get($event->changes, 'old.competitor.email')));

        }

        return $next($event);
    }
}
