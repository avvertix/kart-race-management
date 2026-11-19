<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the User the Enrollment needs something from them before the organizer can confirm it: another Licence after
 * its request was rejected, or the Competitor of an underage driver.
 */
class EnrollmentNeedsAction extends AccountNotification
{
    /**
     * @param  bool  $needsLicence  Whether another Licence is needed, as the request was rejected; otherwise the Competitor
     */
    public function __construct(public Enrollment $enrollment, public bool $needsLicence) {}

    public function type(): NotificationType
    {
        return NotificationType::EnrollmentNeedsAction;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $what = $this->needsLicence
            ? __('The licence request it waited for was rejected: choose another licence or renew again.')
            : __('The driver is underage: name the competitor.');

        return $this->mailMessage()
            ->subject(__('Your enrollment needs your attention'))
            ->line(__('The enrollment of :driver in :championship can\'t be confirmed yet.', ['driver' => $this->enrollment->holder()?->fullName(), 'championship' => $this->enrollment->championship->title]))
            ->line($what)
            ->action(__('View the enrollment'), $this->openUrl());
    }

    /**
     * @return array{enrollment: string, needs_licence: bool}
     */
    public function toArray(User $notifiable): array
    {
        return ['enrollment' => $this->enrollment->ulid, 'needs_licence' => $this->needsLicence];
    }
}
