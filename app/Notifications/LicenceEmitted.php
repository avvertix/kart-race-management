<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Licence;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the applicant the Licence is emitted and ready to download.
 */
class LicenceEmitted extends AccountNotification
{
    public function __construct(public Licence $licence) {}

    public function type(): NotificationType
    {
        return NotificationType::LicenceEmitted;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your licence is ready'))
            ->line(__('The :type licence of :holder for :year is emitted.', [
                'type' => $this->licence->licenceTypeLabel(),
                'holder' => $this->licence->holder->fullName(),
                'year' => $this->licence->validity_year,
            ]))
            ->line(__('Download the temporary licence and use it to enroll in races until the provider sends the final one.'))
            ->action(__('Open the licence'), route('my-licences.show', $this->licence));
    }

    /**
     * @return array{licence: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['licence' => $this->licence->ulid];
    }
}
