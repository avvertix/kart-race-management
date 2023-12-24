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
        ]);
        
        Participant::addLogChange(new EncryptSensibleParticipantData());
    }
}
