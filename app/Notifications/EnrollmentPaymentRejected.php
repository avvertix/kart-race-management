<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the User the organizer could not confirm the payment proof of the Enrollment, so they upload a new one.
 */
class EnrollmentPaymentRejected extends AccountNotification
{
    public function __construct(public Enrollment $enrollment) {}

    public function type(): NotificationType
    {
        return NotificationType::EnrollmentPaymentRejected;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your payment proof was not accepted'))
            ->line(__('The organizer could not confirm the payment proof of the enrollment of :driver in :championship. Upload a new one.', ['driver' => $this->enrollment->holder()?->fullName(), 'championship' => $this->enrollment->championship->title]))
            ->action(__('Upload the payment proof'), $this->openUrl());
    }

    /**
     * @return array{enrollment: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['enrollment' => $this->enrollment->ulid];
    }
}
