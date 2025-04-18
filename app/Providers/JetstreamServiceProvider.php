<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePermissions();

        Jetstream::deleteUsersUsing(DeleteUser::class);
    }

    /**
     * Configure the permissions that are available within the application.
     *
     * @return void
     */
    protected function configurePermissions()
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);

        Jetstream::role('admin', 'Administrator', [
            '*',
        ])->description('Platform administrator');

        Jetstream::role('organizer', 'Organizer', [
            'championship:list',
            'championship:create',
            'championship:update',
            'championship:delete',
            'category:list',
            'category:create',
            'category:update',
            'category:delete',
            'bonus:list',
            'bonus:create',
            'bonus:update',
            'bonus:delete',
            'race:list',
            'race:create',
            'race:update',
            'race:delete',
            'participant:list',
            'participant:view',
            'participant:create',
            'participant:update',
            'participant:delete',
            'tire:list',
            'tire:view',
            'tire:create',
            'tire:update',
            'transponder:view',
            'transponder:create',
            'transponder:update',
            'payment:view',
            'communication:list',
            'communication:view',
            'communication:create',
            'communication:update',
            'communication:delete',
        ])->description('Race and Championship organizer');

        Jetstream::role('racemanager', 'Race manager', [
            'championship:list',
            'category:list',
            'race:list',
            'bonus:list',
            'participant:list',
            'participant:view',
            'participant:create',
            'participant:update',
            'participant:delete',
            'tire:list',
            'tire:view',
            'tire:create',
            'tire:update',
            'transponder:list',
            'transponder:view',
            'transponder:create',
            'transponder:update',
            'transponder:delete',
            'payment:view',
            'communication:list',
            'communication:view',
            'communication:create',
            'communication:update',
            'communication:delete',
        ])->description('Responsible of the race');

        Jetstream::role('tireagent', 'Tire responsible', [
            'championship:list',
            'category:list',
            'race:list',
            'participant:list',
            'participant:view',
            'tire:list',
            'tire:view',
            'tire:create',
            'tire:update',
        ])->description('Responsible of tire management');

        Jetstream::role('timekeeper', 'Timekeeper', [
            'championship:list',
            'category:list',
            'race:list',
            'participant:list',
            'participant:view',
            'transponder:list',
            'transponder:view',
            'transponder:create',
            'transponder:update',
            'transponder:delete',
            'orbits-backup:view',
            'orbits-backup:create',
            'orbits-backup:update',
        ])->description('Time-keeping service');

    }
}
