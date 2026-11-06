<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\GrantEnrollmentEntitlements;
use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Actions\SubmitPaymentProof;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EntitlementMode;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EnrollmentOptionCoverageTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-05 10:00');
    }

    public function test_an_option_covering_the_renewal_pays_the_request_once_the_enrollment_is_paid(): void
    {
        $championship = $this->championship(amount: 18000, coversRenewal: true);
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid])
            ->assertSessionHasNoErrors();

        $enrollment = $championship->enrollments()->sole();
        $licenceRequest = $enrollment->licenceRequest;

        $this->assertSame(EnrollmentPaymentStatus::AwaitingPayment, $enrollment->payment_status);
        $this->assertSame(LicenceRequestPaymentStatus::Covered, $licenceRequest->payment_status);
        $this->assertTrue($licenceRequest->coveringEnrollment->is($enrollment));
        $this->assertFalse($licenceRequest->isPaymentSettled());

        $enrollment->update(['payment_status' => EnrollmentPaymentStatus::Paid]);

        $this->assertTrue($licenceRequest->fresh()->isPaymentSettled());

        $enrollment->update(['payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);

        $this->actingAs($user)->post(route('my-enrollments.withdraw', $enrollment));

        $licenceRequest = $licenceRequest->fresh();

        $this->assertNull($licenceRequest->covering_enrollment_id);
        $this->assertSame(LicenceRequestPaymentStatus::AwaitingPrice, $licenceRequest->payment_status);
    }

    public function test_an_option_not_covering_the_renewal_leaves_the_request_payment_alone(): void
    {
        $championship = $this->championship(amount: 18000, coversRenewal: false);
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->submitted()->awaitingPayment()->create(['validity_year' => 2027]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$licenceRequest->ulid])
            ->assertSessionHasNoErrors();

        $this->assertSame(LicenceRequestPaymentStatus::AwaitingPayment, $licenceRequest->fresh()->payment_status);
    }

    public function test_after_a_rejected_covered_request_the_new_one_is_covered(): void
    {
        Notification::fake();
        $championship = $this->championship(amount: 18000, coversRenewal: true);
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid]);

        $enrollment = $championship->enrollments()->sole();
        $rejected = $enrollment->licenceRequest;
        $rejected->update(['status' => LicenceRequestStatus::Submitted, 'submitted_at' => now()]);

        app(RejectLicenceRequest::class)($rejected, User::factory()->organizer()->create(), 'Wrong licence type.');

        $this->actingAs($user)
            ->put(route('my-enrollments.licence.update', $enrollment), ['subject' => 'renew:'.$previous->ulid])
            ->assertSessionHasNoErrors();

        $renewal = $enrollment->fresh()->licenceRequest;

        $this->assertFalse($renewal->is($rejected));
        $this->assertSame(LicenceRequestPaymentStatus::Covered, $renewal->payment_status);
        $this->assertTrue($renewal->coveringEnrollment->is($enrollment));
    }

    public function test_a_kartsport_promo_is_offered_by_licence_category_and_gives_its_race_credit_once_paid(): void
    {
        Storage::fake('payments');
        $championship = Championship::factory()->openForEnrollment()->create([
            'bonus_enabled' => false,
            'entitlements_enabled' => true,
            'start_at' => now()->setDate(2026, 12, 1),
        ]);
        $easyrace = EnrollmentOption::factory()->for($championship)->create([
            'title' => 'Promo Easyrace',
            'amount' => 12000,
            'reserves_race_number' => true,
            'entitlement_mode' => EntitlementMode::Credit,
            'entitlement_amount' => 1,
            'licence_categories' => [DriverLicence::EASYRACE->value],
        ]);
        $national = EnrollmentOption::factory()->for($championship)->create([
            'title' => 'Promo National',
            'amount' => 20000,
            'licence_categories' => [DriverLicence::LOCAL_NATIONAL->value],
        ]);
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['licence_type' => 'easyrace', 'race_licence_category' => DriverLicence::EASYRACE]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid, 'option' => $national->ulid])
            ->assertSessionHasErrors('option');

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid, 'option' => $easyrace->ulid])
            ->assertSessionHasNoErrors();

        $enrollment = $championship->enrollments()->sole();

        $this->assertSame(12000, $enrollment->amount);

        app(SubmitPaymentProof::class)($enrollment, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        $this->assertSame(0, Entitlement::query()->count());

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('enrollments.payment.confirm', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        app(GrantEnrollmentEntitlements::class)($enrollment->fresh());

        $entitlement = Entitlement::query()->sole();

        $this->assertTrue($entitlement->licence->is($licence));
        $this->assertSame(1, $entitlement->remaining);
    }

    public function test_the_organizer_moves_an_enrollment_to_another_option_until_it_is_paid(): void
    {
        $championship = $this->championship(amount: 12000, coversRenewal: false);
        $other = EnrollmentOption::factory()->for($championship)->create(['title' => 'Promo National', 'amount' => 20000]);
        $enrollment = Enrollment::factory()->for($championship)->create(['amount' => 12000, 'payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->put(route('enrollments.option.update', $enrollment), ['option' => $other->ulid])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(20000, $enrollment->fresh()->amount);
        $this->assertTrue($enrollment->fresh()->option->is($other));

        $enrollment->update(['payment_status' => EnrollmentPaymentStatus::Paid]);

        $this->actingAs($organizer)
            ->put(route('enrollments.option.update', $enrollment), ['option' => $championship->enrollmentOptions()->first()->ulid])
            ->assertSessionHasErrors('option');
    }

    public function test_a_free_option_with_credits_grants_them_once_the_licence_is_verified(): void
    {
        $championship = Championship::factory()->openForEnrollment()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);
        $championship->enrollmentOptions()->sole()->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 2]);
        $user = User::factory()->create();
        $declared = Licence::factory()->for($user)->create(['validity_year' => $championship->start_at->year]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$declared->ulid])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Entitlement::query()->count(), 'A declared licence not verified yet got the credit.');

        $declared->markVerifiedBy(User::factory()->organizer()->create());

        $this->assertSame(2, Entitlement::query()->where('licence_id', $declared->getKey())->sole()->remaining);
    }

    public function test_a_free_option_covering_the_renewal_settles_the_request(): void
    {
        $championship = $this->championship(amount: 0, coversRenewal: true);
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid]);

        $this->assertTrue($championship->enrollments()->sole()->licenceRequest->isPaymentSettled());
    }

    public function test_moving_to_an_option_that_doesnt_cover_the_renewal_uncovers_the_request_and_releases_the_number(): void
    {
        $championship = Championship::factory()->openForEnrollment(amount: 18000, coversRenewal: true, reservesRaceNumber: true)->create([
            'start_at' => now()->setDate(2027, 3, 1)->startOfDay(),
            'end_at' => now()->setDate(2027, 10, 31)->startOfDay(),
        ]);
        $plain = EnrollmentOption::factory()->for($championship)->create(['title' => 'Without renewal', 'amount' => 9000]);
        $user = User::factory()->create();
        $previous = Licence::factory()->for($user)->create(['validity_year' => 2026]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:'.$previous->ulid, 'option' => $championship->enrollmentOptions()->first()->ulid, 'bib' => '33']);

        $enrollment = $championship->enrollments()->sole();

        $this->assertSame(LicenceRequestPaymentStatus::Covered, $enrollment->licenceRequest->payment_status);
        $this->assertSame(1, $enrollment->reservations()->notReleased()->count());

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('enrollments.option.update', $enrollment), ['option' => $plain->ulid])
            ->assertSessionHasNoErrors();

        $this->assertSame(LicenceRequestPaymentStatus::AwaitingPrice, $enrollment->licenceRequest->fresh()->payment_status);
        $this->assertSame(0, $enrollment->reservations()->notReleased()->count());
        $this->assertSame(9000, $enrollment->fresh()->amount);
    }

    private function championship(int $amount, bool $coversRenewal): Championship
    {
        return Championship::factory()->openForEnrollment(amount: $amount, coversRenewal: $coversRenewal)->create([
            'start_at' => now()->setDate(2027, 3, 1)->startOfDay(),
            'end_at' => now()->setDate(2027, 10, 31)->startOfDay(),
        ]);
    }
}
