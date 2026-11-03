<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the applicant the organizer commented on the request and asks to fix it. The comments are read on the request.
 */
class LicenceRequestChangesRequested extends AccountNotification
{
    public function __construct(public LicenceRequest $licenceRequest) {}

    public function type(): NotificationType
    {
        return NotificationType::LicenceRequestChangesRequested;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your licence request needs changes'))
            ->line(__('The organizer commented on the request for the :type licence of :holder and asks you to fix it.', $this->describe($this->licenceRequest)))
            ->action(__('See the comments'), route('licence-requests.edit', $this->licenceRequest))
            ->line(__('Send it again once fixed: the organizer reviews it again.'));
    }

    /**
     * @return array{licence_request: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['licence_request' => $this->licenceRequest->ulid];
    }
}
