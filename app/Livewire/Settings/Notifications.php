<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\NotificationType;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Which notifications the User also receives by email. The copy in the app is always kept.
 */
#[Title('Notification settings')]
class Notifications extends Component
{
    /**
     * Whether to email each notification, keyed by NotificationType value.
     *
     * @var array<string, bool>
     */
    public array $emails = [];

    public function mount(): void
    {
        $this->emails = collect($this->types())
            ->mapWithKeys(fn (NotificationType $type) => [$type->value => $this->user()->wantsEmail($type)])
            ->all();
    }

    /**
     * Saves as soon as a switch is toggled, as users expect from a switch.
     */
    public function updatedEmails(): void
    {
        $this->validate(['emails' => ['array'], 'emails.*' => ['boolean']]);

        $user = $this->user();

        $user->update([
            'notification_preferences' => [
                ...($user->notification_preferences ?? []),
                ...collect($this->types())->mapWithKeys(fn (NotificationType $type) => [$type->value => (bool) ($this->emails[$type->value] ?? false)])->all(),
            ],
        ]);

        Flux::toast(variant: 'success', text: __('Notification settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.settings.notifications', [
            'types' => $this->types(),
            'isAdmin' => $this->user()->isAdmin(),
        ]);
    }

    /**
     * The notifications the User can receive: the organizers' ones only for organizers and admins.
     *
     * @return array<int, NotificationType>
     */
    private function types(): array
    {
        $isOrganizer = $this->user()->canReceiveOrganizerNotifications();

        return array_values(array_filter(NotificationType::cases(), fn (NotificationType $type) => $isOrganizer || ! $type->isForOrganizers()));
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
