<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\Enrollments\RejectEnrollment;
use App\Actions\Orders\PlaceOrder;
use App\Actions\SubmitPaymentProof;
use App\Data\EnrollmentSettingsData;
use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class EnrollmentReviewPaymentTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_organizer_confirms_the_payment_and_the_enrollment_from_its_page(): void
    {
        Storage::fake('payments');
        [$enrollment, $payment] = $this->paidEnrollmentWithProof();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertOk()
            ->assertSee(__('The payment is not confirmed yet.'))
            ->assertSee(route('payment-verification.show', $payment), false)
            ->assertSee(route('enrollments.payment.confirm', $enrollment))
            ->assertDontSee(route('enrollments.confirm', $enrollment));

        $this->actingAs($organizer)
            ->post(route('enrollments.payment.confirm', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(OrderStatus::Paid, $enrollment->order->fresh()->status);

        $this->actingAs($organizer)
            ->post(route('enrollments.confirm', $enrollment))
            ->assertSessionHasNoErrors();

        $this->assertSame(EnrollmentStatus::Confirmed, $enrollment->fresh()->status);
    }

    public function test_the_organizer_rejects_the_payment_proof_from_the_enrollment_page(): void
    {
        Storage::fake('payments');
        [$enrollment] = $this->paidEnrollmentWithProof();

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('enrollments.payment.reject', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(OrderStatus::AwaitingPayment, $enrollment->order->fresh()->status);
    }

    public function test_only_organizers_review_the_enrollment_payment(): void
    {
        Storage::fake('payments');
        [$enrollment] = $this->paidEnrollmentWithProof();

        $this->actingAs($enrollment->user)
            ->post(route('enrollments.payment.confirm', $enrollment))
            ->assertForbidden();

        $this->assertSame(OrderStatus::PaymentSubmitted, $enrollment->order->fresh()->status);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.enrollments.index', [$enrollment->championship, 'status' => EnrollmentStatus::Confirmed->value]))
            ->assertOk()
            ->assertSee('colspan="7"', false);
    }

    public function test_the_enrollments_list_tells_what_each_one_is_missing(): void
    {
        $championship = $this->championship();
        $ready = Enrollment::factory()->for($championship)->create([
            'licence_id' => Licence::factory()->verified()->create(['number' => 'R100001']),
        ]);
        $unverified = Enrollment::factory()->for($championship)->create([
            'licence_id' => Licence::factory()->create(['number' => 'U100001']),
        ]);
        BibReservation::factory()->recycle($championship)->create(['enrollment_id' => $ready->getKey(), 'bib' => 42, 'status' => BibReservationStatus::Pending]);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.enrollments.index', $championship))
            ->assertOk()
            ->assertSeeInOrder(['R100001', '42', __('Ready to confirm')])
            ->assertSeeInOrder(['U100001', __('The declared licence is not verified yet.')]);
    }

    public function test_a_number_confirmed_before_payment_is_released_when_the_enrollment_is_rejected(): void
    {
        $championship = $this->championship();
        $enrollment = Enrollment::factory()->for($championship)->create();
        $reservation = app(PickRaceNumber::class)($enrollment, 77, $enrollment->user);
        $reservation->update(['status' => BibReservationStatus::Confirmed]);

        app(RejectEnrollment::class)($enrollment, 'Not eligible.');

        $this->assertSame(BibReservationStatus::Released, $reservation->fresh()->status);
    }

    /**
     * @return array{0: Enrollment, 1: \App\Models\Payment}
     */
    private function paidEnrollmentWithProof(): array
    {
        $championship = $this->championship();
        $fee = Product::factory()->enrollment()->recycle($championship)->create();
        Price::factory()->for($fee)->create(['amount' => 18000]);
        $user = User::factory()->create();
        $order = app(PlaceOrder::class)($user, $fee);
        $enrollment = Enrollment::factory()->for($championship)->for($user)->create([
            'licence_id' => Licence::factory()->verified()->for($user)->create(),
            'order_id' => $order->getKey(),
        ]);
        $payment = app(SubmitPaymentProof::class)($order, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        return [$enrollment, $payment];
    }

    private function championship(): Championship
    {
        $championship = Championship::factory()->create();

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: now()->subDay(),
            closes_at: now()->addMonth(),
            grants_bib_reservation: true,
            requires_competitor_when_underage: false,
        );
        $championship->save();

        return $championship;
    }
}
