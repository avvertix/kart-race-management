<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Features\LicencesAndProducts;
use App\Livewire\LicenceRequestReview;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Notifications\LicenceRequestReceived;
use App\Notifications\LicenceRequestSubmitted;
use App\Notifications\LicenceRequestVerified;
use Database\Factories\LicenceFactory;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_bell_counts_the_unread_notifications(): void
    {
        $licenceRequest = $this->request();
        $user = $licenceRequest->user;

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee(route('notifications.index'))
            ->assertDontSee('unread notifications');

        $user->notify(new LicenceRequestReceived($licenceRequest));
        $user->notify(new LicenceRequestVerified($licenceRequest));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee(route('notifications.index'))
            ->assertSee('2 unread notifications');
    }

    public function test_the_inbox_lists_the_notifications_newest_first(): void
    {
        $licenceRequest = $this->request();
        $user = $licenceRequest->user;

        $this->travel(-1)->days();
        $user->notify(new LicenceRequestReceived($licenceRequest));
        $this->travelBack();
        $user->notify(new LicenceRequestVerified($licenceRequest));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertSuccessful()
            ->assertSeeInOrder([
                NotificationType::LicenceRequestVerified->label(),
                NotificationType::LicenceRequestReceived->label(),
            ])
            ->assertSee('NAZ E · Giulia Bianchi');
    }

    public function test_opening_a_notification_marks_it_as_read(): void
    {
        $licenceRequest = $this->request();
        $user = $licenceRequest->user;
        $user->notify(new LicenceRequestReceived($licenceRequest));
        $notification = $user->notifications()->sole();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->getKey()))
            ->assertRedirect(route('licence-requests.show', $licenceRequest));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notifications_of_another_user_cannot_be_opened(): void
    {
        $licenceRequest = $this->request();
        $licenceRequest->user->notify(new LicenceRequestReceived($licenceRequest));

        $this->actingAs(User::factory()->create())
            ->get(route('notifications.open', $licenceRequest->user->notifications()->sole()->getKey()))
            ->assertRedirect(route('notifications.index'));

        $this->assertCount(1, $licenceRequest->user->unreadNotifications()->get());
    }

    public function test_the_email_link_of_a_deleted_notification_opens_the_notifications(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('notifications.open', '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d'))
            ->assertRedirect(route('notifications.index'));
    }

    public function test_all_notifications_are_marked_as_read(): void
    {
        $licenceRequest = $this->request();
        $user = $licenceRequest->user;
        $user->notify(new LicenceRequestReceived($licenceRequest));
        $user->notify(new LicenceRequestVerified($licenceRequest));

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertCount(0, $user->unreadNotifications()->get());
    }

    public function test_viewing_the_request_keeps_its_notifications_unread(): void
    {
        $licenceRequest = $this->request();
        $user = $licenceRequest->user;
        $user->notify(new LicenceRequestReceived($licenceRequest));

        $this->actingAs($user)->get(route('licence-requests.show', $licenceRequest))->assertSuccessful();

        $this->assertCount(1, $user->unreadNotifications()->get());
    }

    public function test_reviewing_the_request_keeps_the_organizer_notifications_unread(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = $this->request();
        $organizer->notify(new LicenceRequestSubmitted($licenceRequest));

        Livewire::actingAs($organizer)->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest]);

        $this->assertCount(1, $organizer->unreadNotifications()->get());
    }

    public function test_the_email_link_marks_the_notification_as_read(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = $this->request();
        $organizer->notify(new LicenceRequestSubmitted($licenceRequest));

        $notification = new LicenceRequestSubmitted($licenceRequest);
        $notification->id = $organizer->notifications()->sole()->getKey();

        $this->actingAs($organizer)
            ->get($notification->toMail($organizer)->actionUrl)
            ->assertRedirect(route('licence-request-reviews.show', $licenceRequest));

        $this->assertCount(0, $organizer->unreadNotifications()->get());
    }

    public function test_a_notification_about_a_deleted_request_is_no_longer_available(): void
    {
        $licenceRequest = $this->request();
        $user = $licenceRequest->user;
        $user->notify(new LicenceRequestReceived($licenceRequest));
        $licenceRequest->delete();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertSuccessful()
            ->assertSee('No longer available');

        $this->actingAs($user)
            ->get(route('notifications.open', $user->notifications()->sole()->getKey()))
            ->assertRedirect(route('notifications.index'));
    }

    public function test_the_inbox_needs_early_access(): void
    {
        Feature::define(LicencesAndProducts::class, fn () => false);

        $this->actingAs(User::factory()->create())->get(route('notifications.index'))->assertNotFound();
    }

    private function request(?User $user = null): LicenceRequest
    {
        $factory = LicenceRequest::factory()->submitted()->withLicenceAndHolder(LicenceFactory::holder('Giulia', 'Bianchi'));

        return ($user ? $factory->for($user) : $factory)->create();
    }
}
