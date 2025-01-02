<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
use App\Listeners\ApplyBonusToParticipant;
use App\Listeners\CheckParticipantForWildcard;
use App\Listeners\LogNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
// use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\Events\NotificationSent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        NotificationSent::class => [
            LogNotification::class,
        ],
        ParticipantRegistered::class => [
            ApplyBonusToParticipant::class,
            CheckParticipantForWildcard::class,
        ],
        ParticipantUpdated::class => [
            CheckParticipantForWildcard::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
