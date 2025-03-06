<?php

declare(strict_types=1);

namespace App\Providers;

use App\ActivityLog\EncryptSensibleParticipantData;
use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'participant' => Participant::class,
            'user' => User::class,
            'championship_tire' => ChampionshipTire::class,
            'category' => Category::class,
            'championship' => Championship::class,
            'race' => Race::class,
        ]);

        Participant::addLogChange(new EncryptSensibleParticipantData());

        Blade::if('useCompleteRegistrationForm', function () {
            return config('races.registration.form') === 'complete';
        });

        Date::macro('normalizeToDateString', function (?string $value): ?string {
            if (blank($value)) {
                return null;
            }

            if (Carbon::hasFormat($value, 'd/m/Y')) {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }

            if (Carbon::hasFormat($value, 'd-m-Y')) {
                return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
            }

            if (Carbon::hasFormat($value, 'Y-m-d')) {
                return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            }

            if (Carbon::hasFormat($value, 'Y-d-m')) {
                return Carbon::createFromFormat('Y-d-m', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        });

        Date::macro('normalizeToFormat', function (?string $value, string $format = 'Y-m-d'): ?string {
            if (blank($value)) {
                return null;
            }

            $normalizedValue = Date::normalizeToDateString($value);

            return Carbon::createFromFormat('Y-m-d', $normalizedValue)->format($format);
        });
    }
}
