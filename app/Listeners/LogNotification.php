<?php

namespace App\Listeners;

use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\InteractsWithQueue;

class LogNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Notifications\Events\NotificationSent  $event
     * @return void
     */
    public function handle(NotificationSent $event)
    {
        $notifiableType = class_basename($event->notifiable);
        $notificationType = class_basename($event->notification);

        $notificationTarget = "{$notifiableType} {$event->notifiable->getKey()}";
        
        if($event->notification instanceof ConfirmParticipantRegistration){
            $notificationTarget = "{$notifiableType} {$event->notification->target} {$event->notifiable->getKey()}";
        }

        logs()->info("Notification [{$notificationType}] sent for [$notificationTarget]");
    }
}
