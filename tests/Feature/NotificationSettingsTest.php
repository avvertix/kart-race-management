<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Livewire\Settings\Notifications;
use App\Models\User;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_a_driver_turns_off_an_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.edit'))
            ->assertSuccessful()
            ->assertSee(NotificationType::LicenceRequestVerified->label())
            ->assertDontSee(NotificationType::LicenceRequestSubmitted->label());

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->assertSet('emails.'.NotificationType::LicenceRequestVerified->value, true)
            ->set('emails.'.NotificationType::LicenceRequestVerified->value, false)
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->wantsEmail(NotificationType::LicenceRequestVerified));
        $this->assertTrue($user->wantsEmail(NotificationType::LicenceRequestReceived));
    }

    public function test_admins_turn_on_the_organizers_notifications(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Notifications::class)
            ->assertSee(NotificationType::LicenceRequestSubmitted->label())
            ->assertSee('Admins receive it only with the email on.')
            ->assertSet('emails.'.NotificationType::LicenceRequestSubmitted->value, false)
            ->set('emails.'.NotificationType::LicenceRequestSubmitted->value, true)
            ->call('save');

        $this->assertTrue($admin->fresh()->wantsEmail(NotificationType::LicenceRequestSubmitted));
        $this->assertTrue(User::organizersToNotify(NotificationType::LicenceRequestSubmitted)->contains($admin));
    }
}
