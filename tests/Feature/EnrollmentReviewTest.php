<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\EnrollmentSettingsData;
use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Events\EnrollmentConfirmed;
use App\Events\EnrollmentRevoked;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Illuminate\Support\Facades\Event;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class EnrollmentReviewTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_organizer_lists_the_submitted_enrollments_oldest_first(): void
    {
        $championship = $this->championshipWithEnrollment();
        $organizer = User::factory()->organizer()->create();

        $this->travelTo(now()->subDay());
        Enrollment::factory()->recycle($championship)->create(['licence_id' => Licence::factory()->verified()->create(['holder' => LicenceFactory::holder('Anna', 'First')])]);
        $this->travelBack();
        Enrollment::factory()->recycle($championship)->create(['licence_id' => Licence::factory()->verified()->create(['holder' => LicenceFactory::holder('Bruno', 'Second')])]);
        Enrollment::factory()->confirmed()->recycle($championship)->create(['licence_id' => Licence::factory()->verified()->create(['holder' => LicenceFactory::holder('Carla', 'Confirmed')])]);

        $this->actingAs($organizer)
            ->get(route('championships.show', $championship))
            ->assertSee(route('championships.enrollments.index', $championship));

        $this->actingAs($organizer)
            ->get(route('championships.enrollments.index', $championship))
            ->assertSuccessful()
            ->assertSeeInOrder(['Anna First', 'Bruno Second'])
            ->assertDontSee('Carla Confirmed');

        $this->actingAs($organizer)
            ->get(route('championships.enrollments.index', [$championship, 'status' => EnrollmentStatus::Confirmed->value]))
            ->assertSee('Carla Confirmed')
            ->assertDontSee('Anna First');
    }

    public function test_enrollments_are_reviewed_by_organizers_with_early_access(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('championships.enrollments.index', $enrollment->championship))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->post(route('enrollments.confirm', $enrollment))
            ->assertForbidden();
    }

    public function test_the_organizer_confirms_an_enrollment(): void
    {
        Event::fake([EnrollmentConfirmed::class]);

        $organizer = User::factory()->organizer()->create();
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment())->create(['order_id' => $order->getKey()]);
        $reservation = BibReservation::factory()->pending()->recycle($enrollment->championship)->create(['enrollment_id' => $enrollment->getKey()]);

        $this->actingAs($organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSuccessful()
            ->assertSee('Confirm enrollment');

        $this->actingAs($organizer)
            ->post(route('enrollments.confirm', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $enrollment = $enrollment->fresh();

        $this->assertSame(EnrollmentStatus::Confirmed, $enrollment->status);
        $this->assertSame($organizer->getKey(), $enrollment->confirmed_by);
        $this->assertNotNull($enrollment->confirmed_at);
        $this->assertSame(BibReservationStatus::Confirmed, $reservation->fresh()->status);

        Event::assertDispatched(EnrollmentConfirmed::class, fn (EnrollmentConfirmed $event) => $event->enrollment->is($enrollment));
    }

    public function test_an_enrollment_waiting_for_a_request_cannot_be_confirmed(): void
    {
        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment())->waitingFor(LicenceRequest::factory()->submitted()->create())->create();

        $this->assertConfirmationRefused($enrollment, 'The licence request is not emitted yet.');
    }

    public function test_an_unverified_declared_licence_is_verified_before_confirming(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licence = Licence::factory()->create();
        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment())->create(['licence_id' => $licence->getKey()]);

        $this->assertConfirmationRefused($enrollment, 'The declared licence is not verified yet.');

        $this->actingAs($organizer)
            ->post(route('enrollments.verify-licence', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertTrue($licence->fresh()->isVerified());

        $this->actingAs($organizer)
            ->post(route('enrollments.confirm', $enrollment))
            ->assertSessionHasNoErrors();

        $this->assertSame(EnrollmentStatus::Confirmed, $enrollment->fresh()->status);
    }

    public function test_an_unpaid_enrollment_cannot_be_confirmed(): void
    {
        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment())->create([
            'order_id' => Order::factory()->create(['status' => OrderStatus::PaymentSubmitted])->getKey(),
        ]);

        $this->assertConfirmationRefused($enrollment, 'The payment is not confirmed yet.');
    }

    public function test_an_underage_driver_needs_a_competitor_when_the_championship_asks(): void
    {
        $holder = LicenceFactory::holder('Luca', 'Rossi');
        $holder->birth_date = now()->subYears(12)->toDateString();

        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment(requiresCompetitor: true))->create([
            'licence_id' => Licence::factory()->verified()->create(['holder' => $holder])->getKey(),
        ]);

        $this->assertConfirmationRefused($enrollment, 'The driver is underage and no competitor is named.');
    }

    public function test_the_organizer_rejects_an_enrollment_with_a_reason(): void
    {
        $organizer = User::factory()->organizer()->create();
        $order = Order::factory()->create(['status' => OrderStatus::AwaitingPayment]);
        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment())->create(['order_id' => $order->getKey()]);
        $reservation = BibReservation::factory()->pending()->recycle($enrollment->championship)->create(['enrollment_id' => $enrollment->getKey()]);

        $this->actingAs($organizer)
            ->post(route('enrollments.reject', $enrollment), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($organizer)
            ->post(route('enrollments.reject', $enrollment), ['reason' => 'The licence is not valid for this championship.'])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $enrollment = $enrollment->fresh();

        $this->assertSame(EnrollmentStatus::Rejected, $enrollment->status);
        $this->assertSame('The licence is not valid for this championship.', $enrollment->rejection_reason);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(BibReservationStatus::Released, $reservation->fresh()->status);
    }

    public function test_the_organizer_withdraws_a_confirmed_enrollment(): void
    {
        Event::fake([EnrollmentRevoked::class]);

        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        $enrollment = Enrollment::factory()->confirmed()->recycle($this->championshipWithEnrollment())->create(['order_id' => $order->getKey()]);

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('enrollments.withdraw', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(EnrollmentStatus::Withdrawn, $enrollment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);

        Event::assertDispatched(EnrollmentRevoked::class);
    }

    public function test_the_organizer_enrolls_a_licence_outside_the_period(): void
    {
        $championship = $this->championshipWithEnrollment();
        $championship->enrollment->closes_at = now()->subDay();
        $championship->enrollment->opens_at = now()->subMonth();
        $championship->save();

        $organizer = User::factory()->organizer()->create();
        $licence = Licence::factory()->verified()->create(['number' => 'ON-BEHALF-1']);

        $this->actingAs($organizer)
            ->get(route('championships.enrollments.create', $championship))
            ->assertSuccessful()
            ->assertSee('ON-BEHALF-1');

        $this->actingAs($organizer)
            ->post(route('championships.enrollments.store', $championship), ['licence' => $licence->ulid])
            ->assertRedirect();

        $enrollment = $championship->enrollments()->sole();

        $this->assertTrue($enrollment->licence->is($licence));
        $this->assertTrue($enrollment->user->is($licence->user));
        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->status);
        $this->assertNull($enrollment->order_id);
    }

    public function test_the_organizer_pages_need_enrollment_enabled(): void
    {
        $championship = Championship::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('championships.enrollments.index', $championship))
            ->assertNotFound();

        $this->actingAs($organizer)
            ->post(route('championships.enrollments.store', $championship), ['licence' => Licence::factory()->verified()->create()->ulid])
            ->assertNotFound();

        $this->assertSame(0, $championship->enrollments()->count());
    }

    private function assertConfirmationRefused(Enrollment $enrollment, string $reason): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSee($reason);

        $this->actingAs($organizer)
            ->post(route('enrollments.confirm', $enrollment))
            ->assertSessionHasErrors('enrollment');

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);
    }

    private function championshipWithEnrollment(bool $requiresCompetitor = false): Championship
    {
        $championship = Championship::factory()->create();

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: now()->subDay(),
            closes_at: now()->addMonth(),
            requires_competitor_when_underage: $requiresCompetitor,
        );

        $championship->save();

        return $championship;
    }
}
