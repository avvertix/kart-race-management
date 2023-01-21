<?php

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
            'race:list',
            'race:create',
            'race:update',
            'race:delete',
            'participant:list',
            'participant:create',
            'participant:update',
            'participant:delete',
        ])->description('Race and Championship organizer');
        
        Jetstream::role('racemanager', 'Race manager', [
            'championship:list',
            'race:list',
            'participant:list',
            'participant:create',
            'participant:update',
            'participant:delete',
        ])->description('Responsible of the race');

        Jetstream::role('tireagent', 'Tire responsible', [
            'championship:list',
            'race:list',
            'participant:list',
        ])->description('Responsible of tire management');
        
        Jetstream::role('timekeeper', 'Timekeeper', [
            'championship:list',
            'race:list',
            'participant:list',
        ])->description('Time-keeping service');

    }
}
