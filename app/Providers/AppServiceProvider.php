<?php

namespace App\Providers;

use App\ActivityLog\EncryptSensibleParticipantData;
use App\Models\Category;
use App\Models\ChampionshipTire;
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
            'championship_tire' => ChampionshipTire::class,
            'category' => Category::class,
        ]);
        
        Participant::addLogChange(new EncryptSensibleParticipantData());
    }
}
