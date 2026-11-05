<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\ConfirmEnrollment;
use App\Actions\Enrollments\RejectEnrollment;
use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Actions\LicenceRequests\SubmitLicenceRequest;
use App\Actions\Orders\ConfirmOrderPayment;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\RejectOrderPayment;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use App\Notifications\EnrollmentConfirmed;
use App\Notifications\EnrollmentNeedsAction;
use App\Notifications\EnrollmentRejected;
use App\Notifications\OrderPaymentConfirmed;
use App\Notifications\OrderPaymentRejected;
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class EnrollmentNotificationsTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_driver_is_told_when_the_enrollment_is_confirmed_or_rejected(): void
    {
        Notification::fake();
        $championship = $this->championship();
        $confirmed = Enrollment::factory()->for($championship)->create(['licence_id' => Licence::factory()->verified()]);
        $rejected = Enrollment::factory()->for($championship)->create();

        app(ConfirmEnrollment::class)($confirmed, User::factory()->organizer()->create());
        app(RejectEnrollment::class)($rejected, 'Not eligible.');

        Notification::assertSentTo($confirmed->user, EnrollmentConfirmed::class, fn (EnrollmentConfirmed $notification) => $notification->enrollment->is($confirmed));
        Notification::assertSentTo($rejected->user, EnrollmentRejected::class, function (EnrollmentRejected $notification) use ($rejected) {
            return $notification->enrollment->is($rejected)
                && str_contains(implode(' ', $notification->toMail($rejected->user)->introLines), 'Not eligible.');
        });
    }

    public function test_the_driver_is_told_when_the_enrollment_needs_another_licence(): void
    {
        Notification::fake();
        $licenceRequest = LicenceRequest::factory()->submitted()->create();
        $enrollment = Enrollment::factory()->for($this->championship())->waitingFor($licenceRequest)->create();

        app(RejectLicenceRequest::class)($licenceRequest, User::factory()->organizer()->create(), 'Documents missing.');

        Notification::assertSentTo($enrollment->user, EnrollmentNeedsAction::class);
    }

    public function test_the_driver_is_told_when_the_submitted_request_shows_an_underage_driver_without_competitor(): void
    {
        Notification::fake();
        $championship = $this->championship(requiresCompetitor: true);
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->create(['validity_year' => $championship->start_at->year]);
        $enrollment = Enrollment::factory()->for($championship)->waitingFor($licenceRequest)->create();

        app(SubmitLicenceRequest::class)($licenceRequest);

        Notification::assertSentTo($enrollment->user, EnrollmentNeedsAction::class);
    }

    public function test_the_buyer_is_told_when_the_payment_is_confirmed_or_rejected(): void
    {
        Notification::fake();
        $paid = Order::factory()->create(['status' => OrderStatus::PaymentSubmitted]);
        $refused = Order::factory()->create(['status' => OrderStatus::PaymentSubmitted]);
        $organizer = User::factory()->organizer()->create();

        app(ConfirmOrderPayment::class)($paid, $organizer);
        app(RejectOrderPayment::class)($refused);

        Notification::assertSentTo($paid->user, OrderPaymentConfirmed::class);
        Notification::assertSentTo($refused->user, OrderPaymentRejected::class);
    }

    public function test_the_notifications_open_the_enrollment_or_the_renewal_to_start(): void
    {
        $championship = $this->championship();
        $enrollment = Enrollment::factory()->for($championship)->create(['licence_id' => Licence::factory()->verified()]);
        $user = $enrollment->user;

        app(ConfirmEnrollment::class)($enrollment, User::factory()->organizer()->create());

        $this->actingAs($user)
            ->get(route('notifications.open', $user->notifications()->sole()->getKey()))
            ->assertRedirect(route('my-enrollments.show', $enrollment));

        $package = Product::factory()->create();
        Price::factory()->for($package)->create();
        $package->grants()->create(['kind' => ProductGrantKind::LicenceEmission]);
        $order = app(PlaceOrder::class)($user, $package);
        $order->update(['status' => OrderStatus::PaymentSubmitted]);

        app(ConfirmOrderPayment::class)($order, User::factory()->organizer()->create());

        $this->actingAs($user)
            ->get(route('notifications.open', $user->notifications()->where('type', OrderPaymentConfirmed::class)->sole()->getKey()))
            ->assertRedirect(route('my-licences.index'));
    }

    public function test_the_dashboard_lists_the_enrollments_of_current_and_future_championships(): void
    {
        $user = User::factory()->create();
        $closed = $this->championship(open: false);
        $confirmed = Enrollment::factory()->for($closed)->for($user)->create(['status' => EnrollmentStatus::Confirmed]);
        $past = Championship::factory()->create(['start_at' => today()->subYears(2), 'end_at' => today()->subYears(2)->addMonths(6)]);
        $old = Enrollment::factory()->for($past)->for($user)->create(['status' => EnrollmentStatus::Confirmed]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee(__('Your enrollments'))
            ->assertSee(route('my-enrollments.show', $confirmed))
            ->assertDontSee(route('my-enrollments.show', $old));
    }

    private function championship(bool $open = true, bool $requiresCompetitor = false): Championship
    {
        return Championship::factory()
            ->openForEnrollment(requiresCompetitor: $requiresCompetitor, opensAt: now()->subMonth(), closesAt: $open ? now()->addMonth() : now()->subDay())
            ->create();
    }
}
