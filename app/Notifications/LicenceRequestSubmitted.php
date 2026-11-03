<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the organizers a Licence Request waits for their review, also when sent again after changes.
 */
class LicenceRequestSubmitted extends AccountNotification
{
    public function __construct(public LicenceRequest $licenceRequest, public bool $isResubmission = false) {}

    public function type(): NotificationType
    {
        return NotificationType::LicenceRequestSubmitted;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $replacements = $this->describe($this->licenceRequest);

        return $this->mailMessage()
            ->subject($this->isResubmission
                ? __('Licence request of :holder fixed, to review', $replacements)
                : __('New licence request of :holder to review', $replacements))
            ->line($this->isResubmission
                ? __('The licence request of :holder (:type) was changed and sent again.', $replacements)
                : __('A licence request of :holder (:type) was submitted.', $replacements))
            ->action(__('Review the request'), route('licence-request-reviews.show', $this->licenceRequest));
    }

    /**
     * @return array{licence_request: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['licence_request' => $this->licenceRequest->ulid];
    }
}
