<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the User the organizer confirmed the payment of the Enrollment.
 */
class EnrollmentPaymentConfirmed extends AccountNotification
{
    public function __construct(public Enrollment $enrollment) {}

    public function type(): NotificationType
    {
        return NotificationType::EnrollmentPaymentConfirmed;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your payment is confirmed'))
            ->line(__('The organizer confirmed the payment of the enrollment of :driver in :championship.', ['driver' => $this->enrollment->holder()?->fullName(), 'championship' => $this->enrollment->championship->title]))
            ->action(__('View the enrollment'), $this->openUrl());
    }

    /**
     * @return array{enrollment: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['enrollment' => $this->enrollment->ulid];
    }
}
