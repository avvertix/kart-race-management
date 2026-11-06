<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Data\HolderData;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_a_championship_without_enrollment_shows_nothing_new(): void
    {
        $championship = Championship::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('public.championships.enrollment', $championship))
            ->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->post(route('public.championships.enrollment.store', $championship), [])
            ->assertNotFound();

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.show', $championship))
            ->assertDontSee(route('championships.enrollments.index', $championship));
    }

    public function test_the_driver_enrolls_a_licence_in_a_free_championship(): void
    {
        $championship = $this->openChampionship();
        $driver = User::factory()->create();
        $licence = Licence::factory()->for($driver)->create();
        Licence::factory()->for($driver)->create(['validity_year' => now()->year - 1, 'number' => 'OLD1', 'holder' => $licence->holder]);

        $this->actingAs($driver)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSuccessful()
            ->assertSee($licence->number)
            ->assertDontSee('OLD1');

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid])
            ->assertRedirect(route('my-enrollments.show', $championship->enrollments()->sole()));

        $enrollment = $championship->enrollments()->sole();

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->status);
        $this->assertTrue($enrollment->licence->is($licence));
        $this->assertTrue($enrollment->user->is($driver));

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid])
            ->assertSessionHasErrors('licence');

        $this->assertSame(1, $championship->enrollments()->count());
    }

    public function test_a_paid_option_asks_the_enrollment_to_pay_its_amount(): void
    {
        $championship = Championship::factory()->openForEnrollment(amount: 15000)->create();

        $driver = User::factory()->create();
        $licence = Licence::factory()->for($driver)->create();

        $response = $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid]);

        $enrollment = $championship->enrollments()->sole();

        $response->assertRedirect(route('my-enrollments.show', $enrollment));
        $this->assertSame(EnrollmentPaymentStatus::AwaitingPayment, $enrollment->payment_status);
        $this->assertSame(15000, $enrollment->amount);
        $this->assertTrue($enrollment->option->is($championship->enrollmentOptions()->sole()));
    }

    public function test_only_own_licences_valid_in_the_championship_year_can_be_enrolled(): void
    {
        $championship = $this->openChampionship();
        $driver = User::factory()->create();

        foreach ([
            Licence::factory()->create(),
            Licence::factory()->for($driver)->create(['validity_year' => now()->year - 1]),
        ] as $licence) {
            $this->actingAs($driver)
                ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid])
                ->assertSessionHasErrors('licence');
        }

        $this->assertSame(0, $championship->enrollments()->count());
    }

    public function test_enrollment_is_refused_outside_the_period(): void
    {
        $championship = Championship::factory()->openForEnrollment(opensAt: now()->addDay())->create();
        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.Licence::factory()->for($driver)->create()->ulid])
            ->assertSessionHasErrors('enrollment');

        $this->assertSame(0, $championship->enrollments()->count());
    }

    public function test_an_underage_driver_names_the_competitor(): void
    {
        $championship = $this->openChampionship(requiresCompetitor: true);
        $driver = User::factory()->create();
        $licence = Licence::factory()->for($driver)->create(['holder' => $this->holderBornYearsAgo(12)]);
        $competitor = Licence::factory()->for($driver)->create(['includes_competitor' => true, 'holder' => $this->holderBornYearsAgo(45)]);

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid])
            ->assertSessionHasErrors('competitor_licence');

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid, 'competitor_licence' => $competitor->ulid])
            ->assertSessionHasNoErrors();

        $this->assertTrue($championship->enrollments()->sole()->competitorLicence->is($competitor));
    }

    public function test_an_enrollment_waiting_for_a_request_moves_to_the_emitted_licence(): void
    {
        Notification::fake();

        $championship = $this->openChampionship();
        $licenceRequest = LicenceRequest::factory()->verified()->create(['payment_status' => LicenceRequestPaymentStatus::Paid]);

        $this->actingAs($licenceRequest->user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$licenceRequest->ulid])
            ->assertSessionHasNoErrors();

        $enrollment = $championship->enrollments()->sole();

        $this->assertNull($enrollment->licence_id);
        $this->assertTrue($enrollment->licenceRequest->is($licenceRequest));

        $licence = app(EmitLicence::class)($licenceRequest, User::factory()->organizer()->create(), 'A123456', now()->year, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        $enrollment = $enrollment->fresh();

        $this->assertTrue($enrollment->licence->is($licence));
        $this->assertNull($enrollment->licence_request_id);
        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->status);
    }

    public function test_an_enrollment_stays_submitted_when_its_request_is_rejected(): void
    {
        Notification::fake();

        $licenceRequest = LicenceRequest::factory()->submitted()->create();
        $enrollment = Enrollment::factory()->waitingFor($licenceRequest)->create();

        app(RejectLicenceRequest::class)($licenceRequest, User::factory()->organizer()->create(), 'Not valid.');

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);
    }

    public function test_the_driver_withdraws_a_submitted_enrollment_awaiting_payment(): void
    {
        $driver = User::factory()->create();
        $enrollment = Enrollment::factory()->for($driver)->create(['amount' => 9000, 'payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);

        $this->actingAs($driver)
            ->post(route('my-enrollments.withdraw', $enrollment))
            ->assertRedirect(route('my-enrollments.show', $enrollment));

        $this->assertSame(EnrollmentStatus::Withdrawn, $enrollment->fresh()->status);
    }

    public function test_the_driver_cannot_withdraw_a_confirmed_enrollment(): void
    {
        $driver = User::factory()->create();
        $enrollment = Enrollment::factory()->confirmed()->for($driver)->create();

        $this->actingAs($driver)
            ->post(route('my-enrollments.withdraw', $enrollment))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->post(route('my-enrollments.withdraw', Enrollment::factory()->create()))
            ->assertForbidden();

        $this->assertSame(EnrollmentStatus::Confirmed, $enrollment->fresh()->status);
    }

    private function openChampionship(bool $requiresCompetitor = false): Championship
    {
        return Championship::factory()->openForEnrollment(requiresCompetitor: $requiresCompetitor)->create();
    }

    private function holderBornYearsAgo(int $years): HolderData
    {
        $holder = LicenceFactory::holder('Luca', 'Rossi');
        $holder->birth_date = now()->subYears($years)->toDateString();

        return $holder;
    }
}
