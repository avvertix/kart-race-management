<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\ChangeEnrollmentOption;
use App\Actions\Enrollments\CreateEnrollment;
use App\Actions\Enrollments\RecordEnrollmentPayment;
use App\Actions\Enrollments\ReviewEnrollmentPayment;
use App\Actions\SubmitPaymentProof;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\EntitlementMode;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\PaymentChannelType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EnrollmentDepositTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_the_organizer_sets_a_deposit_and_the_balance_due_date_on_an_option(): void
    {
        $championship = Championship::factory()->openForEnrollment()->create(['entitlements_enabled' => true]);
        $organizer = User::factory()->organizer()->create();
        $gold = ['title' => 'Gold', 'amount' => '950', 'deposit_amount' => '300', 'balance_due_on' => '2027-03-31'];

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $championship), [...$gold, 'deposit_amount' => '950'])
            ->assertSessionHasErrors('deposit_amount');

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $championship), [...$gold, 'balance_due_on' => ''])
            ->assertSessionHasErrors('balance_due_on');

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $championship), [...$gold, 'title' => 'Free', 'amount' => '0', 'deposit_amount' => '10'])
            ->assertSessionHasErrors('deposit_amount');

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $championship), ['title' => 'Silver', 'amount' => '600', 'deposit_amount' => '', 'balance_due_on' => '2027-03-31'])
            ->assertSessionHasNoErrors();

        $this->assertNull($championship->enrollmentOptions()->where('title', 'Silver')->sole()->balance_due_on);

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $championship), $gold)
            ->assertSessionHasNoErrors();

        $option = $championship->enrollmentOptions()->where('title', 'Gold')->sole();

        $this->assertSame(30000, $option->deposit_amount);
        $this->assertSame('2027-03-31', $option->balance_due_on->toDateString());

        $this->actingAs($organizer)
            ->get(route('championships.enrollment-settings.edit', $championship))
            ->assertSee(__('Deposit :deposit, balance by :date', ['deposit' => '€300.00', 'date' => '31 March 2027']));
    }

    public function test_a_paid_deposit_unlocks_the_covered_renewal_and_the_race_credits_but_not_the_confirmation(): void
    {
        [$championship, $gold] = $this->moto();
        $user = User::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);

        $renewal = LicenceRequest::factory()->withLicenceAndHolder()->for($user)->create();
        $waiting = Enrollment::factory()->for($championship)->for($user)->waitingFor($renewal)->create();
        app(ChangeEnrollmentOption::class)($waiting, $gold);

        $this->assertSame(95000, $enrollment->amount);
        $this->assertSame(30000, $enrollment->deposit_amount);
        $this->assertContains(__('The payment is not confirmed yet.'), $enrollment->missingForConfirmation());

        app(RecordEnrollmentPayment::class)($enrollment, $organizer, PaymentChannelType::BANK_TRANSFER, 30000);
        app(RecordEnrollmentPayment::class)($waiting->fresh(), $organizer, PaymentChannelType::CASH, 30000);

        $enrollment->refresh();
        $this->assertSame(EnrollmentPaymentStatus::DepositPaid, $enrollment->payment_status);
        $this->assertTrue($enrollment->isDepositPaid());
        $this->assertSame([__('The balance is not paid yet.')], $enrollment->missingForConfirmation());
        $this->assertSame(6, Entitlement::query()->where('source_id', $enrollment->getKey())->sole()->amount);
        $this->assertTrue($renewal->fresh()->isPaymentSettled());
        $this->assertSame(65000, $enrollment->balance());

        $this->actingAs($user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertSee(__('Pay the balance of :balance by :date.', ['balance' => '€650.00', 'date' => '31 March 2027']));
    }

    public function test_the_driver_pays_the_deposit_first_or_everything_at_once(): void
    {
        [$championship, $gold] = $this->moto();
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);

        $this->actingAs($user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertSee(__('Pay a deposit of :deposit now and the balance of :balance by :date, or :amount at once.', ['deposit' => '€300.00', 'balance' => '€650.00', 'date' => '31 March 2027', 'amount' => '€950.00']));

        Storage::fake('payments');
        app(SubmitPaymentProof::class)($enrollment, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));
        app(ReviewEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), confirm: true, amount: 95000);

        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->fresh()->payment_status);
    }

    public function test_the_balance_is_paid_with_a_new_proof_after_the_deposit(): void
    {
        Storage::fake('payments');
        [$championship, $gold] = $this->moto();
        $user = User::factory()->create();
        $organizer = User::factory()->organizer()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);

        app(RecordEnrollmentPayment::class)($enrollment, $organizer, PaymentChannelType::CASH, 30000);

        $this->actingAs($user)
            ->post(route('my-enrollments.payment.store', $enrollment), ['proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')])
            ->assertSessionHasNoErrors();

        $this->assertSame(EnrollmentPaymentStatus::PaymentSubmitted, $enrollment->fresh()->payment_status);
        $this->assertTrue($enrollment->fresh()->isDepositPaid());

        app(ReviewEnrollmentPayment::class)($enrollment, $organizer, confirm: true, amount: 65000);

        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->fresh()->payment_status);
    }

    public function test_after_the_deposit_the_driver_is_asked_for_the_balance_and_the_organizer_sees_the_enrollment_terms(): void
    {
        [$championship, $gold] = $this->moto();
        $user = User::factory()->create();
        $organizer = User::factory()->organizer()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);

        app(RecordEnrollmentPayment::class)($enrollment, $organizer, PaymentChannelType::CASH, 30000);
        $gold->update(['deposit_amount' => 40000]);

        $this->actingAs($organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSee(__('Deposit :deposit, balance by :date', ['deposit' => '€300.00', 'date' => '31 March 2027']));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee(__('Payment').': '.EnrollmentPaymentStatus::DepositPaid->label());

        $this->actingAs($organizer)
            ->post(route('enrollments.confirm', $enrollment))
            ->assertSessionHasErrors();

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);
    }

    public function test_a_rejected_balance_proof_leaves_the_deposit_paid(): void
    {
        Storage::fake('payments');
        [$championship, $gold] = $this->moto();
        $user = User::factory()->create();
        $organizer = User::factory()->organizer()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);

        app(RecordEnrollmentPayment::class)($enrollment, $organizer, PaymentChannelType::CASH, 30000);
        app(SubmitPaymentProof::class)($enrollment->fresh(), UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));
        app(ReviewEnrollmentPayment::class)($enrollment, $organizer, confirm: false);

        $this->assertSame(EnrollmentPaymentStatus::DepositPaid, $enrollment->fresh()->payment_status);
    }

    public function test_less_than_the_deposit_unlocks_nothing(): void
    {
        [$championship, $gold] = $this->moto();
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);

        app(RecordEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), PaymentChannelType::CASH, 20000);

        $enrollment->refresh();
        $this->assertSame(EnrollmentPaymentStatus::AwaitingPayment, $enrollment->payment_status);
        $this->assertFalse($enrollment->isDepositPaid());
        $this->assertSame(0, Entitlement::query()->count());
    }

    public function test_without_a_deposit_only_the_full_amount_unlocks_the_enrollment(): void
    {
        $championship = Championship::factory()->openForEnrollment(amount: 18000)->create();
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $championship->enrollmentOptions()->sole());

        app(RecordEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), PaymentChannelType::CASH, 10000);

        $this->assertFalse($enrollment->fresh()->isDepositPaid());
        $this->assertSame(EnrollmentPaymentStatus::AwaitingPayment, $enrollment->fresh()->payment_status);
    }

    public function test_the_enrollment_form_and_the_organizer_list_show_deposit_and_balance(): void
    {
        [$championship, $gold] = $this->moto();
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee(__('Deposit :deposit, balance by :date', ['deposit' => '€300.00', 'date' => '31 March 2027']));

        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);
        app(RecordEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), PaymentChannelType::CASH, 30000);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.enrollments.index', $championship))
            ->assertSee(EnrollmentPaymentStatus::DepositPaid->label())
            ->assertSee(__(':paid paid, :balance due', ['paid' => '€300.00', 'balance' => '€650.00']));
    }

    /**
     * The moto championship with its Gold option: €950 with a €300 deposit, the balance by 31 March 2027, covering the
     * renewal and giving 6 race credits.
     *
     * @return array{0: Championship, 1: EnrollmentOption}
     */
    private function moto(): array
    {
        $championship = Championship::factory()->openForEnrollment()->create(['entitlements_enabled' => true]);

        $gold = EnrollmentOption::factory()->for($championship)->create([
            'title' => 'Gold',
            'amount' => 95000,
            'deposit_amount' => 30000,
            'balance_due_on' => CarbonImmutable::parse('2027-03-31'),
            'covers_licence_renewal' => true,
            'entitlement_mode' => EntitlementMode::Credit,
            'entitlement_amount' => 6,
        ]);

        return [$championship, $gold];
    }
}
