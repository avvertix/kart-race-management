<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Actions\Orders\PlaceOrder;
use App\Data\EnrollmentSettingsData;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
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
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class RenewWhileEnrollingTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-05 10:00');
    }

    public function test_renewing_while_enrolling_creates_the_request_and_the_enrollment_waiting_for_it(): void
    {
        $championship = $this->championship();
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026, 'number' => 'A200001']);

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee('value="renew:'.$previous->ulid.'"', false)
            ->assertSee('Renew for 2027');

        $response = $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid]);

        $licenceRequest = LicenceRequest::query()->sole();
        $enrollment = $championship->enrollments()->sole();

        $response->assertRedirect(route('licence-requests.edit', $licenceRequest));

        $this->assertSame(LicenceRequestStatus::Draft, $licenceRequest->status);
        $this->assertSame(2027, $licenceRequest->validity_year);
        $this->assertSame('A200001', $licenceRequest->previous_licence_number);
        $this->assertTrue($enrollment->licenceRequest->is($licenceRequest));

        $this->actingAs($user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertSee('Licence request to complete')
            ->assertSee(route('licence-requests.edit', $licenceRequest));

        $this->actingAs($user)
            ->get(route('licence-requests.show', $licenceRequest))
            ->assertSee(route('my-enrollments.show', $enrollment));
    }

    public function test_a_renewal_package_chosen_while_enrolling_covers_the_request(): void
    {
        $championship = $this->championship();
        $package = $this->package($championship, withRenewal: true);
        $enrollmentOnly = $this->package($championship, withRenewal: false);
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026]);

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee($package->title)
            ->assertSee($enrollmentOnly->title);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid, 'product' => $enrollmentOnly->ulid])
            ->assertSessionHasErrors('product');

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid, 'product' => $package->ulid])
            ->assertSessionHasNoErrors();

        $enrollment = $championship->enrollments()->sole();
        $licenceRequest = $enrollment->licenceRequest;

        $this->assertTrue($enrollment->order->lines->sole()->product->is($package));
        $this->assertSame(LicenceRequestPaymentStatus::Covered, $licenceRequest->payment_status);
        $this->assertTrue($licenceRequest->coveringOrder->is($enrollment->order));
        $this->assertFalse($licenceRequest->isPaymentSettled());
    }

    public function test_after_a_rejected_request_the_driver_picks_another_licence_keeping_the_payment(): void
    {
        Notification::fake();
        $championship = $this->championship();
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->submitted()->create(['validity_year' => 2027]);
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Paid]);
        $enrollment = Enrollment::factory()->for($championship)->waitingFor($licenceRequest)->create(['order_id' => $order->getKey()]);
        $declared = Licence::factory()->for($user)->create(['validity_year' => 2027]);

        app(RejectLicenceRequest::class)($licenceRequest, User::factory()->organizer()->create(), 'Documents missing.');

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);

        $this->actingAs($user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertSee('The licence request was rejected: choose another licence.')
            ->assertSee('value="licence:'.$declared->ulid.'"', false);

        $this->actingAs($user)
            ->put(route('my-enrollments.licence.update', $enrollment), ['subject' => 'licence:'.$declared->ulid])
            ->assertRedirect(route('my-enrollments.show', $enrollment));

        $enrollment = $enrollment->fresh();

        $this->assertTrue($enrollment->licence->is($declared));
        $this->assertNull($enrollment->licence_request_id);
        $this->assertSame($order->getKey(), $enrollment->order_id);
    }

    public function test_a_package_covers_a_new_request_after_its_request_is_rejected(): void
    {
        Notification::fake();
        $championship = $this->championship();
        $package = $this->package($championship, withRenewal: true);
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid, 'product' => $package->ulid]);

        $enrollment = $championship->enrollments()->sole();
        $enrollment->order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);
        $rejected = $enrollment->licenceRequest;
        $rejected->update(['status' => LicenceRequestStatus::Submitted, 'submitted_at' => now()]);

        app(RejectLicenceRequest::class)($rejected, User::factory()->organizer()->create(), 'Wrong licence type.');

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);
        $this->assertTrue($enrollment->order->fresh()->canStartLicenceRenewal());

        $this->actingAs($user)
            ->put(route('my-enrollments.licence.update', $enrollment), ['subject' => 'renew:'.$previous->ulid])
            ->assertSessionHasNoErrors();

        $enrollment = $enrollment->fresh();
        $renewal = $enrollment->licenceRequest;

        $this->assertFalse($renewal->is($rejected));
        $this->assertSame(LicenceRequestPaymentStatus::Covered, $renewal->payment_status);
        $this->assertTrue($renewal->coveringOrder->is($enrollment->order));
        $this->assertTrue($renewal->isPaymentSettled());
    }

    public function test_restarting_a_freed_package_renewal_moves_the_waiting_enrollment_to_it(): void
    {
        Notification::fake();
        $championship = $this->championship();
        $package = $this->package($championship, withRenewal: true);
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid, 'product' => $package->ulid]);

        $enrollment = $championship->enrollments()->sole();
        $enrollment->order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);
        $rejected = $enrollment->licenceRequest;
        $rejected->update(['status' => LicenceRequestStatus::Submitted, 'submitted_at' => now()]);
        app(RejectLicenceRequest::class)($rejected, User::factory()->organizer()->create(), 'Wrong licence type.');

        $this->actingAs($user)
            ->post(route('licence-requests.store'), ['order' => $enrollment->order->ulid, 'licence' => $previous->ulid])
            ->assertSessionMissing('flash.bannerStyle');

        $renewal = LicenceRequest::query()->latest('id')->first();

        $this->assertTrue($enrollment->fresh()->licenceRequest->is($renewal));
        $this->assertSame(1, $championship->enrollments()->count());

        $this->actingAs($user)
            ->delete(route('licence-requests.destroy', $renewal))
            ->assertSessionHasErrors('request');
    }

    public function test_starting_the_renewal_of_a_package_bought_on_its_own_enrolls_at_once(): void
    {
        $championship = $this->championship();
        $closed = Championship::factory()->create(['title' => 'Closed championship', 'start_at' => now()->setDate(2027, 3, 1)]);
        $package = $this->package($championship, withRenewal: true);
        $package->grants()->create(['kind' => ProductGrantKind::Enrollment, 'championship_id' => $closed->getKey()]);
        $user = User::factory()->create();
        $order = app(PlaceOrder::class)($user, $package);
        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSee(__('Renewal covered by :package', ['package' => $package->title]));

        $this->actingAs($user)
            ->post(route('licence-requests.store'), ['order' => $order->ulid])
            ->assertSessionHas('flash.bannerStyle', 'warning');

        $this->assertSame(1, $championship->enrollments()->count());
        $this->assertSame(0, $closed->enrollments()->count());

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertDontSee(__('Renewal covered by :package', ['package' => $package->title]));
    }

    private function championship(): Championship
    {
        $championship = Championship::factory()->create([
            'start_at' => now()->setDate(2027, 3, 1)->startOfDay(),
            'end_at' => now()->setDate(2027, 10, 31)->startOfDay(),
        ]);

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: now()->subDay(),
            closes_at: now()->addMonth(),
            requires_competitor_when_underage: false,
        );
        $championship->save();

        return $championship;
    }

    private function package(Championship $championship, bool $withRenewal): Product
    {
        $package = Product::factory()->create(['title' => $withRenewal ? 'Renewal and enrollment' : 'Enrollment and two races']);
        Price::factory()->for($package)->create();

        if ($withRenewal) {
            $package->grants()->create(['kind' => ProductGrantKind::LicenceEmission]);
        }

        $package->grants()->create(['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey()]);

        return $package;
    }
}
