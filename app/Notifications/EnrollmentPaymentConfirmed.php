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
    /**
     * What is still to pay when the payment was confirmed, in cents: the mail is sent later, after other payments maybe.
     */
    public int $balance;

    public function __construct(public Enrollment $enrollment)
    {
        $this->balance = $enrollment->balance();
    }

    public function type(): NotificationType
    {
        return NotificationType::EnrollmentPaymentConfirmed;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your payment is confirmed'))
            ->line(__('The organizer confirmed the payment of the enrollment of :driver in :championship.', ['driver' => $this->enrollment->holder()?->fullName(), 'championship' => $this->enrollment->championship->title]))
            ->lineIf($this->balance > 0, __('Still to pay: :amount.', ['amount' => $this->enrollment->currency->format($this->balance)]))
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
