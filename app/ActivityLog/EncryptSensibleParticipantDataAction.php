<?php

declare(strict_types=1);

namespace App\ActivityLog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Activitylog\Actions\LogActivityAction;

class EncryptSensibleParticipantDataAction extends LogActivityAction
{
    protected function transformChanges(Model $activity): void
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];

        if (Arr::has($changes, ['attributes.driver.email']) && Str::contains(Arr::get($changes, 'attributes.driver.email'), '@')) {

            Arr::set($changes, 'attributes.driver.email', Crypt::encryptString(Arr::get($changes, 'attributes.driver.email')));
            Arr::set($changes, 'old.driver.email', Crypt::encryptString(Arr::get($changes, 'old.driver.email')));

        }

        if (Arr::has($changes, ['attributes.competitor.email']) && Str::contains(Arr::get($changes, 'attributes.competitor.email'), '@')) {

            Arr::set($changes, 'attributes.competitor.email', Crypt::encryptString(Arr::get($changes, 'attributes.competitor.email')));
            Arr::set($changes, 'old.competitor.email', Crypt::encryptString(Arr::get($changes, 'old.competitor.email')));

        }

        $activity->attribute_changes = collect($changes);
    }
}
