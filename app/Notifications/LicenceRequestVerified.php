<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the applicant the request is verified and how to pay it, or that the enrollment covers it.
 */
class LicenceRequestVerified extends AccountNotification
{
    public function __construct(public LicenceRequest $licenceRequest) {}

    public function type(): NotificationType
    {
        return NotificationType::LicenceRequestVerified;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = $this->mailMessage()
            ->subject(__('Your licence request is verified'))
            ->line(__('The organizer checked the licence request of :holder (:type).', $this->describe($this->licenceRequest)));

        if ($this->licenceRequest->payment_status === LicenceRequestPaymentStatus::Covered) {
            return $mail
                ->line(__('It is covered by your enrollment: there is nothing to pay. The organizer now requests your licence from :provider.', ['provider' => $this->licenceRequest->provider?->label()]))
                ->action(__('View the request'), $this->openUrl());
        }

        return $mail
            ->line(__('Pay :amount so the organizer can request your licence.', ['amount' => $this->licenceRequest->amountLabel()]))
            ->line($this->bankTransferLine())
            ->line(__('Use the following text as the bank transfer reason:').' '.$this->licenceRequest->ulid)
            ->action(__('Upload the payment proof'), $this->openUrl('#payment'))
            ->line(__('You can also pay by cash or credit card at the organizer\'s office.'));
    }

    /**
     * @return array{licence_request: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['licence_request' => $this->licenceRequest->ulid];
    }
}
