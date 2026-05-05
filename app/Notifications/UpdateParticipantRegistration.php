<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class UpdateParticipantRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Participant $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('Your registration for :race has been updated', ['race' => $notifiable->race->title]))
            ->greeting(Lang::get('Hi, there are updates on your registration!', ['race' => $notifiable->race->title]))
            ->line(Lang::get('The registration of **:name** for the race :race has been updated.', [
                'name' => "{$notifiable->first_name} {$notifiable->last_name}",
                'race' => $notifiable->race->title,
            ]))
            ->action(Lang::get('View the participation'), $notifiable->qrCodeUrl())
            ->salutation(Lang::get('This notification was sent by :organizer as you or your team registered for a race.', ['organizer' => config('races.organizer.name')]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
