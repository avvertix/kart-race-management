<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the User the Enrollment was rejected, and why: by the organizer, or because the driver was already enrolled.
 */
class EnrollmentRejected extends AccountNotification
{
    public function __construct(public Enrollment $enrollment) {}

    public function type(): NotificationType
    {
        return NotificationType::EnrollmentRejected;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return $this->mailMessage()
            ->subject(__('Your enrollment was rejected'))
            ->line(__('The enrollment of :driver in :championship was rejected.', ['driver' => $this->enrollment->holder()?->fullName(), 'championship' => $this->enrollment->championship->title]))
            ->line(__('Reason: :reason', ['reason' => $this->enrollment->rejection_reason]))
            ->action(__('View the enrollment'), $this->openUrl())
            ->line(__('Write to the organizer for any question, or about a refund if you already paid.'));
    }

    /**
     * @return array{enrollment: string}
     */
    public function toArray(User $notifiable): array
    {
        return ['enrollment' => $this->enrollment->ulid];
    }
}
