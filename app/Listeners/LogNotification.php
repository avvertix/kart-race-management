<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Notifications\Events\NotificationSent;

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
     * @return void
     */
    public function handle(NotificationSent $event)
    {
        $notifiableType = class_basename($event->notifiable);
        $notificationType = class_basename($event->notification);

        $notificationTarget = "{$notifiableType} {$event->notifiable->getKey()}";

        if ($event->notification instanceof ConfirmParticipantRegistration) {
            $notificationTarget = "{$notifiableType} {$event->notification->target} {$event->notifiable->getKey()}";
        }

        logs()->info("Notification [{$notificationType}] sent for [$notificationTarget]");
    }
}
