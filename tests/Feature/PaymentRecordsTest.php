<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\ChangeEnrollmentOption;
use App\Actions\Enrollments\RecordEnrollmentPayment;
use App\Actions\Enrollments\ReviewEnrollmentPayment;
use App\Actions\Enrollments\WithdrawEnrollment;
use App\Actions\LicenceRequests\RecordLicenceRequestPaymentAtOffice;
use App\Actions\LicenceRequests\ReviewLicenceRequestPayment;
use App\Actions\SubmitPaymentProof;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EntitlementMode;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\PaymentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Payment;
use App\Models\PaymentChannelType;
use App\Models\User;
use App\Notifications\EnrollmentPaymentConfirmed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class PaymentRecordsTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('payments');
    }

    public function test_a_proof_uploaded_by_the_driver_is_a_submitted_bank_transfer_without_amount(): void
    {
        $enrollment = $this->enrollmentToPay(18000);

        $this->actingAs($enrollment->user)
            ->post(route('my-enrollments.payment.store', $enrollment), ['proof' => $this->proof()])
            ->assertRedirect(route('my-enrollments.show', $enrollment));

        $payment = $enrollment->payments()->sole();

        $this->assertSame(PaymentStatus::Submitted, $payment->status);
        $this->assertSame(PaymentChannelType::BANK_TRANSFER, $payment->channel);
        $this->assertNull($payment->amount);
        $this->assertSame(EnrollmentPaymentStatus::PaymentSubmitted, $enrollment->fresh()->payment_status);
    }

    public function test_the_organizer_confirms_a_proof_with_the_amount_received_and_the_rest_stays_due(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();
        $first = app(SubmitPaymentProof::class)($enrollment, $this->proof());

        $this->actingAs($organizer)
            ->post(route('enrollments.payment.confirm', $enrollment), ['amount' => '100'])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $first->refresh();
        $this->assertSame(PaymentStatus::Confirmed, $first->status);
        $this->assertSame(10000, $first->amount);
        $this->assertSame($organizer->getKey(), $first->reviewed_by);
        $this->assertNotNull($first->reviewed_at);

        $enrollment->refresh();
        $this->assertSame(10000, $enrollment->paidAmount());
        $this->assertSame(EnrollmentPaymentStatus::AwaitingPayment, $enrollment->payment_status);

        app(SubmitPaymentProof::class)($enrollment, $this->proof());
        app(ReviewEnrollmentPayment::class)($enrollment, $organizer, confirm: true, amount: 8000);

        $enrollment->refresh();
        $this->assertSame(18000, $enrollment->paidAmount());
        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->payment_status);
        $this->assertNotNull($enrollment->paid_at);
    }

    public function test_confirming_a_proof_without_an_amount_is_refused(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();
        app(SubmitPaymentProof::class)($enrollment, $this->proof());

        $this->actingAs($organizer)
            ->post(route('enrollments.payment.confirm', $enrollment), ['amount' => ''])
            ->assertSessionHasErrors('amount');

        try {
            app(ReviewEnrollmentPayment::class)($enrollment, $organizer, confirm: true);
            $this->fail('A proof was confirmed without an amount.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $this->assertSame(PaymentStatus::Submitted, $enrollment->payments()->sole()->status);
        $this->assertSame(EnrollmentPaymentStatus::PaymentSubmitted, $enrollment->fresh()->payment_status);
    }

    public function test_a_rejected_proof_is_kept_and_a_new_one_can_be_uploaded(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();
        $rejected = app(SubmitPaymentProof::class)($enrollment, $this->proof());

        app(ReviewEnrollmentPayment::class)($enrollment, $organizer, confirm: false);

        $rejected->refresh();
        $this->assertSame(PaymentStatus::Rejected, $rejected->status);
        $this->assertSame($organizer->getKey(), $rejected->reviewed_by);
        $this->assertSame(EnrollmentPaymentStatus::AwaitingPayment, $enrollment->fresh()->payment_status);

        app(SubmitPaymentProof::class)($enrollment->fresh(), $this->proof());

        $this->assertSame(2, $enrollment->payments()->count());
        $this->assertSame(0, $enrollment->fresh()->paidAmount());
    }

    public function test_the_organizer_records_a_payment_received_without_a_proof(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->post(route('enrollments.payment.record', $enrollment), ['channel' => PaymentChannelType::CASH->value, 'amount' => '180'])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $payment = $enrollment->payments()->sole();

        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame(PaymentChannelType::CASH, $payment->channel);
        $this->assertSame(18000, $payment->amount);
        $this->assertNull($payment->path);
        $this->assertSame($organizer->getKey(), $payment->recorded_by);
        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->fresh()->payment_status);
    }

    public function test_a_proof_and_a_card_payment_add_up(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();

        app(SubmitPaymentProof::class)($enrollment, $this->proof());
        app(ReviewEnrollmentPayment::class)($enrollment, $organizer, confirm: true, amount: 5000);
        app(RecordEnrollmentPayment::class)($enrollment->fresh(), $organizer, PaymentChannelType::CREDIT_CARD, 13000);

        $this->assertSame(18000, $enrollment->fresh()->paidAmount());
        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->fresh()->payment_status);
    }

    public function test_a_payment_cannot_be_recorded_while_a_proof_waits_for_review_nor_by_the_driver(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        app(SubmitPaymentProof::class)($enrollment, $this->proof());

        $this->actingAs($enrollment->user)
            ->post(route('enrollments.payment.record', $enrollment), ['channel' => PaymentChannelType::CASH->value, 'amount' => '180'])
            ->assertForbidden();

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('enrollments.payment.record', $enrollment), ['channel' => PaymentChannelType::CASH->value, 'amount' => '180'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(1, $enrollment->payments()->count());
    }

    public function test_the_enrollment_pages_list_the_payment_records(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();

        app(SubmitPaymentProof::class)($enrollment, $this->proof());
        app(ReviewEnrollmentPayment::class)($enrollment, $organizer, confirm: true, amount: 5000);
        app(RecordEnrollmentPayment::class)($enrollment->fresh(), $organizer, PaymentChannelType::CASH, 2000);

        foreach ([[$organizer, 'enrollments.show'], [$enrollment->user, 'my-enrollments.show']] as [$user, $route]) {
            $this->actingAs($user)
                ->get(route($route, $enrollment))
                ->assertOk()
                ->assertSeeInOrder([PaymentChannelType::BANK_TRANSFER->localizedName(), '€50.00', PaymentStatus::Confirmed->label()])
                ->assertSeeInOrder([PaymentChannelType::CASH->localizedName(), '€20.00'])
                ->assertSee(__('Paid :paid of :amount', ['paid' => '€70.00', 'amount' => '€180.00']));
        }
    }

    public function test_a_confirmed_licence_request_proof_records_the_request_amount(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->verified()->awaitingPayment(5000)->create();
        $proof = app(SubmitPaymentProof::class)($licenceRequest, $this->proof());

        app(ReviewLicenceRequestPayment::class)($licenceRequest, $organizer, confirm: true);

        $proof->refresh();
        $this->assertSame(PaymentStatus::Confirmed, $proof->status);
        $this->assertSame(5000, $proof->amount);
        $this->assertSame(LicenceRequestPaymentStatus::Paid, $licenceRequest->fresh()->payment_status);
    }

    public function test_a_licence_request_paid_at_the_office_has_a_record_without_proof(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->verified()->awaitingPayment(5000)->create();

        app(RecordLicenceRequestPaymentAtOffice::class)($licenceRequest, $organizer, PaymentChannelType::CREDIT_CARD);

        $payment = $licenceRequest->payments()->sole();

        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame(PaymentChannelType::CREDIT_CARD, $payment->channel);
        $this->assertSame(5000, $payment->amount);
        $this->assertNull($payment->path);
        $this->assertSame($organizer->getKey(), $payment->recorded_by);
    }

    public function test_the_amount_accepts_a_decimal_comma_and_at_most_two_decimals(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->post(route('enrollments.payment.record', $enrollment), ['channel' => PaymentChannelType::CASH->value, 'amount' => '50.123'])
            ->assertSessionHasErrors('amount');

        $this->actingAs($organizer)
            ->post(route('enrollments.payment.record', $enrollment), ['channel' => PaymentChannelType::CASH->value, 'amount' => '50,50'])
            ->assertSessionHasNoErrors();

        $this->assertSame(5050, $enrollment->payments()->sole()->amount);
    }

    public function test_the_race_fee_discount_waits_for_the_full_amount(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $enrollment->option->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 6]);
        $organizer = User::factory()->organizer()->create();

        app(RecordEnrollmentPayment::class)($enrollment, $organizer, PaymentChannelType::CASH, 10000);

        $this->assertSame(0, Entitlement::query()->count());

        app(RecordEnrollmentPayment::class)($enrollment->fresh(), $organizer, PaymentChannelType::CASH, 8000);

        $this->assertSame(6, Entitlement::query()->sole()->amount);
    }

    public function test_the_confirmation_mail_tells_what_is_still_to_pay(): void
    {
        Notification::fake();
        $enrollment = $this->enrollmentToPay(18000);

        app(RecordEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), PaymentChannelType::CASH, 10000);

        Notification::assertSentTo($enrollment->user, EnrollmentPaymentConfirmed::class, function (EnrollmentPaymentConfirmed $notification) use ($enrollment) {
            return in_array(__('Still to pay: :amount.', ['amount' => '€80.00']), $notification->toMail($enrollment->user)->introLines, true);
        });
    }

    public function test_the_option_cannot_change_once_something_is_paid(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $cheaper = EnrollmentOption::factory()->for($enrollment->championship)->create(['amount' => 5000]);

        app(RecordEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), PaymentChannelType::CASH, 6000);

        $this->expectException(ValidationException::class);

        app(ChangeEnrollmentOption::class)($enrollment->fresh(), $cheaper);
    }

    public function test_a_withdrawn_enrollment_takes_no_payment_and_its_driver_is_told_about_refunds(): void
    {
        $enrollment = $this->enrollmentToPay(18000);
        $organizer = User::factory()->organizer()->create();

        app(RecordEnrollmentPayment::class)($enrollment, $organizer, PaymentChannelType::CASH, 6000);
        app(WithdrawEnrollment::class)($enrollment->fresh(), byOrganizer: true);

        try {
            app(RecordEnrollmentPayment::class)($enrollment->fresh(), $organizer, PaymentChannelType::CASH, 12000);
            $this->fail('A payment was recorded on a withdrawn enrollment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }

        $this->actingAs($enrollment->user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertSee(__('For a refund, email the organizer.'));
    }

    public function test_a_record_without_proof_has_nothing_to_open(): void
    {
        $enrollment = $this->enrollmentToPay(18000);

        app(RecordEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), PaymentChannelType::CASH, 6000);

        $this->get($enrollment->payments()->sole()->downloadUrl)->assertNotFound();
    }

    public function test_race_participant_payments_have_no_amount_channel_or_status(): void
    {
        $payment = Payment::factory()->forParticipant()->create();

        $this->assertNull($payment->fresh()->amount);
        $this->assertNull($payment->fresh()->channel);
        $this->assertNull($payment->fresh()->status);
    }

    private function enrollmentToPay(int $amount): Enrollment
    {
        $championship = Championship::factory()->openForEnrollment(amount: $amount)->create();
        $user = User::factory()->create();

        return Enrollment::factory()->for($championship)->for($user)->create([
            'licence_id' => Licence::factory()->verified()->for($user)->create(),
            'amount' => $amount,
            'payment_status' => EnrollmentPaymentStatus::AwaitingPayment,
        ]);
    }

    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');
    }
}
