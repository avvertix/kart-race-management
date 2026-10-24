<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\LogNotification;
use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RegistrationForm;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Facades\Nightwatch;
use Laravel\Nightwatch\Records\Request;

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
        Nightwatch::user(fn (Authenticatable $user) => []);
        Nightwatch::redactRequests(function (Request $request) {
            $request->ip = '***';
        });

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands($this->app->isProduction());

        $this->configureAuthorization();
        $this->configureEvents();
        $this->configureRateLimiting();

        Relation::enforceMorphMap([
            'participant' => Participant::class,
            'user' => User::class,
            'championship_tire' => ChampionshipTire::class,
            'category' => Category::class,
            'championship' => Championship::class,
            'race' => Race::class,
        ]);

        Blade::if('useStandardRegistrationForm', function (?Race $race = null): bool {
            return RegistrationForm::resolve($race) !== RegistrationForm::Minimal;
        });

        Blade::if('useCompleteRegistrationForm', function (?Race $race = null): bool {
            return RegistrationForm::resolve($race) === RegistrationForm::Complete;
        });

        Date::macro('normalizeToDateString', function (?string $value): ?string {
            if (blank($value)) {
                return null;
            }

            if (Carbon::hasFormat($value, 'd/m/Y')) {
                return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
            }

            if (Carbon::hasFormat($value, 'd-m-Y')) {
                return Carbon::createFromFormat('d-m-Y', $value)->toDateString();
            }

            if (Carbon::hasFormat($value, 'Y-m-d')) {
                return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
            }

            return Carbon::parse($value)->toDateString();
        });

        Date::macro('normalizeToFormat', function (?string $value, string $format = 'Y-m-d'): ?string {
            if (blank($value)) {
                return null;
            }

            $normalizedValue = Date::normalizeToDateString($value);

            return Carbon::parse($normalizedValue)->format($format);
        });
    }

    /**
     * Configure the driver gates. Policies are auto-discovered from App\Policies.
     */
    private function configureAuthorization(): void
    {
        Gate::define('drivers:view', fn (User $user) => ! $user->isAdmin() && $user->hasPermission('drivers:view'));
        Gate::define('drivers:create', fn (User $user) => ! $user->isAdmin() && $user->hasPermission('drivers:create'));
        Gate::define('drivers:update', fn (User $user) => ! $user->isAdmin() && $user->hasPermission('drivers:update'));
        Gate::define('drivers:delete', fn (User $user) => ! $user->isAdmin() && $user->hasPermission('drivers:delete'));
    }

    /**
     * Register event listeners. Event discovery is disabled because
     * App\Listeners also holds participant pipeline stages, not listeners.
     */
    private function configureEvents(): void
    {
        Event::listen(NotificationSent::class, LogNotification::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (HttpRequest $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
