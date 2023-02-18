<?php

namespace App\Providers;

use App\ActivityLog\EncryptSensibleParticipantData;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Relation::enforceMorphMap([
            'participant' => Participant::class,
            'user' => User::class,
        ]);
        
        Participant::addLogChange(new EncryptSensibleParticipantData());
    }
}
