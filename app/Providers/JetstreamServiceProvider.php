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
            '',
        ])->description('Race and Championship organizer');
        
        Jetstream::role('racemanager', 'Race manager', [
            '',
        ])->description('Responsible of the race');

        Jetstream::role('tireagent', 'Tire responsible', [
            '',
        ])->description('Responsible of tire management');
        
        Jetstream::role('timekeeper', 'Timekeeper', [
            '',
        ])->description('Time-keeping service');

    }
}
