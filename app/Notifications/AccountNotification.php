<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A notification to a User's account: kept in the app, and sent by email unless the User turned it off in their
 * notification settings. Queued once the transaction that caused it commits.
 */
abstract class AccountNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    abstract public function type(): NotificationType;

    /**
     * @return array<int, string>
     */
    final public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * The copy in the app is always kept, so turning the email off never hides what needs attention.
     */
    final public function shouldSend(User $notifiable, string $channel): bool
    {
        return $channel !== 'mail' || $notifiable->wantsEmail($this->type());
    }

    /**
     * A mail ending with where to turn these emails off.
     */
    protected function mailMessage(): MailMessage
    {
        return (new MailMessage)
            ->salutation(__('Manage your email notifications in your [notification settings](:url).', ['url' => route('notifications.edit')]));
    }

    /**
     * Where the email's button leads: through the notification kept in the app, so that following the link marks it
     * as read, like opening it from the notifications list. A fragment is kept by the browser across the redirect.
     */
    protected function openUrl(string $fragment = ''): string
    {
        return route('notifications.open', $this->id).$fragment;
    }

    /**
     * The organizer's bank account to pay to.
     */
    protected function bankTransferLine(): string
    {
        return __('Pay by bank transfer to:').' '.collect([
            config('races.organizer.bank_holder'),
            config('races.organizer.bank'),
            config('races.organizer.bank_account'),
        ])->filter()->join(', ');
    }

    /**
     * The licence and holder a request is for, to describe it in a sentence.
     *
     * @return array{type: ?string, holder: ?string}
     */
    protected function describe(LicenceRequest $licenceRequest): array
    {
        return [
            'type' => $licenceRequest->licenceTypeLabel(),
            'holder' => $licenceRequest->holder?->fullName(),
        ];
    }
}
