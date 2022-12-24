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
            '',
        ])->description('');
        
        Jetstream::role('organizer', 'Organizer', [
            '',
        ])->description('');
        
        Jetstream::role('race-control', 'Race control', [
            '',
        ])->description('');

        Jetstream::role('tire-responsible', 'Tire responsible', [
            '',
        ])->description('');
        
        Jetstream::role('timekeeper', 'Timekeeper', [
            '',
        ])->description('');
    }
}
