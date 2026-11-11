<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Reminds the User that the balance of the Enrollment is due soon.
 */
class EnrollmentBalanceDue extends AccountNotification
{
    /**
     * What is still to pay when the reminder was sent, in cents.
     */
    public int $balance;

    public function __construct(public Enrollment $enrollment)
    {
        $this->balance = $enrollment->balance();
    }

    public function type(): NotificationType
    {
        return NotificationType::EnrollmentBalanceDue;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('The balance of your enrollment is due soon'))
            ->line(__('The enrollment of :driver in :championship is not paid in full yet.', ['driver' => $this->enrollment->holder()?->fullName(), 'championship' => $this->enrollment->championship->title]))
            ->line(__('Pay the balance of :balance by :date.', ['balance' => $this->enrollment->currency->format($this->balance), 'date' => $this->enrollment->balance_due_on?->isoFormat('D MMMM YYYY')]))
            ->line($this->bankTransferLine())
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
