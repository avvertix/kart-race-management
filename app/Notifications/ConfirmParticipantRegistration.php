<?php

namespace App\Notifications;

use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class ConfirmParticipantRegistration extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        /**
         * The target for the verification. Default driver. Acceptable values: driver, competitor
         */
        public string $target = 'driver'
    )
    {
        // Ensure that this will be queued only after all
        // database transactions are committed
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(Participant $notifiable)
    {
        return (new MailMessage)
                    ->subject(Lang::get('Verify the email and confirm the participation to race'))
                    ->line(Lang::get('We received a request to register **:name** as driver for the race :race', [
                        'name' => "{$notifiable->first_name} {$notifiable->last_name}",
                        'race' => $notifiable->race->title,
                    ]))
                    ->action(Lang::get('Confirm the participation'), $this->verificationUrl($notifiable))
                    ->line(Lang::get('We do ask the confirmation to ensure that the email address is valid and as a proof that can replace the signature on the printed request for participation.'))
                    ->line(Lang::get('By confirming the participation you agree to the race regulation.'))
                    ->salutation(Lang::get('[View the participation](:link)', ['link' => $notifiable->qrCodeUrl()]))
                    ;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }


    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl(Participant $notifiable)
    {
        return URL::temporarySignedRoute(
            'participant.sign.create',
            Carbon::now()->addMinutes(Config::get('participant.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'p' => $notifiable->signatureContent(),
                'hash' => sha1($notifiable->getEmailForVerification($this->target)),
            ]
        );
    }
}
