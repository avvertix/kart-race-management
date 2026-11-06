<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class RenewWhileEnrollingTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
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

    public function test_after_a_rejected_request_the_driver_picks_another_licence_keeping_the_payment(): void
    {
        Notification::fake();
        $championship = $this->championship();
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->submitted()->create(['validity_year' => 2027]);
        $enrollment = Enrollment::factory()->for($championship)->waitingFor($licenceRequest)->create(['amount' => 9000, 'payment_status' => EnrollmentPaymentStatus::Paid]);
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
        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->payment_status);
        $this->assertSame(9000, $enrollment->amount);
    }

    private function championship(): Championship
    {
        return Championship::factory()->openForEnrollment()->create([
            'start_at' => now()->setDate(2027, 3, 1)->startOfDay(),
            'end_at' => now()->setDate(2027, 10, 31)->startOfDay(),
        ]);
    }
}
