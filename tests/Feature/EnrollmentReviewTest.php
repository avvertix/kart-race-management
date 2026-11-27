<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentConfirmed;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Payment;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Illuminate\Support\Facades\Event;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EnrollmentReviewTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
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
        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment())->create(['amount' => 9000, 'payment_status' => EnrollmentPaymentStatus::Paid]);
        Payment::factory()->confirmedFor($enrollment, 9000)->create();
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

    public function test_the_enrollment_page_links_the_licence_request_it_waits_for(): void
    {
        $championship = $this->championshipWithEnrollment();
        $submitted = Enrollment::factory()->recycle($championship)->waitingFor(LicenceRequest::factory()->withLicenceAndHolder()->submitted()->create())->create();
        $draft = Enrollment::factory()->recycle($championship)->waitingFor(LicenceRequest::factory()->withLicenceAndHolder(LicenceFactory::distinctHolder())->create())->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('enrollments.show', $submitted))
            ->assertSee(route('licence-request-reviews.show', $submitted->licenceRequest), false);

        $this->actingAs($organizer)
            ->get(route('enrollments.show', $draft))
            ->assertSee(__('Waiting for the licence request'))
            ->assertDontSee(route('licence-request-reviews.show', $draft->licenceRequest), false);
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
            'amount' => 9000,
            'payment_status' => EnrollmentPaymentStatus::PaymentSubmitted,
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
        $enrollment = Enrollment::factory()->recycle($this->championshipWithEnrollment())->create(['amount' => 9000, 'payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);
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
        $this->assertSame(BibReservationStatus::Released, $reservation->fresh()->status);
    }

    public function test_a_confirmed_enrollment_cannot_be_withdrawn(): void
    {
        $enrollment = Enrollment::factory()->confirmed()->recycle($this->championshipWithEnrollment())->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('enrollments.withdraw', $enrollment))
            ->assertSessionHasErrors('enrollment');

        $this->assertSame(EnrollmentStatus::Confirmed, $enrollment->fresh()->status);
    }

    public function test_the_organizer_enrolls_a_licence_before_the_period_opens(): void
    {
        $championship = $this->championshipWithEnrollment();
        $championship->enrollment->opens_at = now()->addDay();
        $championship->enrollment->closes_at = now()->addMonth();
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
        $this->assertSame(EnrollmentPaymentStatus::NotDue, $enrollment->payment_status);
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
        return Championship::factory()->openForEnrollment(requiresCompetitor: $requiresCompetitor)->create();
    }
}
