<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the applicant the organizer rejected the request, and why.
 */
class LicenceRequestRejected extends AccountNotification
{
    public function __construct(public LicenceRequest $licenceRequest) {}

    public function type(): NotificationType
    {
        return NotificationType::LicenceRequestRejected;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your licence request was rejected'))
            ->line(__('The organizer rejected the request for the :type licence of :holder.', $this->describe($this->licenceRequest)))
            ->line(__('Reason: :reason', ['reason' => $this->licenceRequest->rejection_reason]))
            ->action(__('View the request'), route('licence-requests.show', $this->licenceRequest))
            ->line(__('Write to the organizer for any question, or about a refund if you already paid.'));
    }

    /**
     * @return array{licence_request: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['licence_request' => $this->licenceRequest->ulid];
    }
}
