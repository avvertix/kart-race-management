<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the applicant their Licence Request was submitted.
 */
class LicenceRequestReceived extends AccountNotification
{
    public function __construct(public LicenceRequest $licenceRequest) {}

    public function type(): NotificationType
    {
        return NotificationType::LicenceRequestReceived;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('We received your licence request'))
            ->line(__('We received your request for the :type licence of :holder.', $this->describe($this->licenceRequest)))
            ->line(__('The organizer checks it and tells you if anything needs to be fixed.'))
            ->action(__('View the request'), route('licence-requests.show', $this->licenceRequest));
    }

    /**
     * @return array{licence_request: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['licence_request' => $this->licenceRequest->ulid];
    }
}
