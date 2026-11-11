<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\BringBackEnrollment;
use App\Actions\Enrollments\ChangeEnrollmentOption;
use App\Actions\Enrollments\CreateEnrollment;
use App\Actions\Enrollments\CreditEnrollmentPayment;
use App\Actions\Enrollments\RecordEnrollmentPayment;
use App\Actions\Enrollments\RejectEnrollment;
use App\Actions\Enrollments\WithdrawEnrollment;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\EntitlementMode;
use App\Enums\LicenceRequestStatus;
use App\Enums\PromoCodeStatus;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Participant;
use App\Models\PaymentChannelType;
use App\Models\Race;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EnrollmentOptionChangeAndWithdrawalTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    private Championship $championship;

    private EnrollmentOption $gold;

    private EnrollmentOption $silver;

    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->championship = Championship::factory()->openForEnrollment()->create(['entitlements_enabled' => true]);
        $this->gold = $this->option('Gold', 95000, 30000);
        $this->silver = $this->option('Silver', 60000, 20000);
        $this->organizer = User::factory()->organizer()->create();
    }

    public function test_the_driver_changes_option_while_the_window_is_open_and_the_organizer_also_afterwards(): void
    {
        $enrollment = $this->enrollWith($this->silver, paid: 20000);

        $this->actingAs($enrollment->user)
            ->put(route('my-enrollments.option.update', $enrollment), ['option' => $this->gold->ulid])
            ->assertRedirect(route('my-enrollments.show', $enrollment));

        $enrollment->refresh();
        $this->assertTrue($enrollment->option->is($this->gold));
        $this->assertSame(75000, $enrollment->balance());
        $this->assertSame(EnrollmentPaymentStatus::AwaitingPayment, $enrollment->payment_status);

        $this->closeTheWindow();

        $this->actingAs($enrollment->user)
            ->put(route('my-enrollments.option.update', $enrollment), ['option' => $this->silver->ulid])
            ->assertForbidden();

        $this->actingAs($this->organizer)
            ->put(route('enrollments.option.update', $enrollment), ['option' => $this->silver->ulid])
            ->assertSessionHasNoErrors();

        $this->assertTrue($enrollment->fresh()->option->is($this->silver));
        $this->assertSame(EnrollmentPaymentStatus::DepositPaid, $enrollment->fresh()->payment_status);
    }

    public function test_an_enrollment_paid_in_full_keeps_its_option(): void
    {
        $enrollment = $this->enrollWith($this->gold, paid: 95000);

        $this->actingAs($enrollment->user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertDontSee(route('my-enrollments.option.update', $enrollment));

        $this->expectException(ValidationException::class);

        app(ChangeEnrollmentOption::class)($enrollment, $this->silver);
    }

    public function test_paying_more_than_the_new_option_shows_what_to_refund(): void
    {
        $enrollment = $this->enrollWith($this->gold, paid: 70000);

        app(ChangeEnrollmentOption::class)($enrollment, $this->silver);

        $enrollment->refresh();
        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->payment_status);
        $this->assertSame(10000, $enrollment->overpaidAmount());

        $this->actingAs($this->organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSee(__(':amount to refund', ['amount' => '€100.00']));
    }

    public function test_only_an_enrollment_paid_in_full_is_confirmed_and_then_it_is_final(): void
    {
        $enrollment = $this->enrollWith($this->gold, paid: 30000);

        $this->assertContains(__('The balance is not paid yet.'), $enrollment->missingForConfirmation());

        $this->actingAs($this->organizer)
            ->post(route('enrollments.confirm', $enrollment))
            ->assertSessionHasErrors();

        app(RecordEnrollmentPayment::class)($enrollment, $this->organizer, PaymentChannelType::CASH, 65000);

        $this->actingAs($this->organizer)
            ->post(route('enrollments.confirm', $enrollment))
            ->assertSessionHasNoErrors();

        $this->assertSame(EnrollmentStatus::Confirmed, $enrollment->fresh()->status);

        $this->actingAs($this->organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertDontSee(route('enrollments.withdraw', $enrollment))
            ->assertDontSee(route('enrollments.reject', $enrollment));

        foreach ([fn () => app(WithdrawEnrollment::class)($enrollment->fresh()), fn () => app(RejectEnrollment::class)($enrollment->fresh(), 'No.')] as $close) {
            try {
                $close();
                $this->fail('A confirmed enrollment was closed.');
            } catch (ValidationException) {
                $this->assertSame(EnrollmentStatus::Confirmed, $enrollment->fresh()->status);
            }
        }
    }

    public function test_a_withdrawn_enrollment_comes_back_when_the_organizer_records_a_payment_before_the_first_race(): void
    {
        Race::factory()->for($this->championship)->create(['event_start_at' => now()->addMonth(), 'event_end_at' => now()->addMonth()->addDay()]);
        $enrollment = $this->enrollWith($this->gold, paid: 30000);
        app(WithdrawEnrollment::class)($enrollment);

        $this->actingAs($this->organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSee(route('enrollments.payment.record', $enrollment));

        app(RecordEnrollmentPayment::class)($enrollment->fresh(), $this->organizer, PaymentChannelType::BANK_TRANSFER, 65000);

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->status);
        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->payment_status);
    }

    public function test_a_withdrawn_enrollment_cannot_come_back_once_the_first_race_started(): void
    {
        Race::factory()->for($this->championship)->create(['event_start_at' => now()->subDay(), 'event_end_at' => now()->addDay()]);
        $enrollment = $this->enrollWith($this->gold, paid: 30000);
        app(WithdrawEnrollment::class)($enrollment);

        $this->expectException(ValidationException::class);

        app(RecordEnrollmentPayment::class)($enrollment->fresh(), $this->organizer, PaymentChannelType::CASH, 65000);
    }

    public function test_the_driver_changes_only_to_an_option_offered_to_their_licence_category(): void
    {
        $enrollment = $this->enrollWith($this->silver, paid: 20000);
        $international = EnrollmentOption::factory()->for($this->championship)->create([
            'title' => 'International',
            'amount' => 120000,
            'licence_categories' => [DriverLicence::LOCAL_INTERNATIONAL->value],
        ]);
        $enrollment->licence->update(['race_licence_category' => DriverLicence::LOCAL_NATIONAL]);

        $this->actingAs($enrollment->user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertSee('Gold')
            ->assertDontSee('International');

        $this->actingAs($enrollment->user)
            ->put(route('my-enrollments.option.update', $enrollment), ['option' => $international->ulid])
            ->assertSessionHasErrors('option');

        $this->assertTrue($enrollment->fresh()->option->is($this->silver));
    }

    public function test_a_withdrawn_enrollment_does_not_come_back_when_the_driver_enrolled_again(): void
    {
        $enrollment = $this->enrollWith($this->gold, paid: 30000);
        app(WithdrawEnrollment::class)($enrollment);
        app(CreateEnrollment::class)($enrollment->user, $this->championship, $enrollment->licence, null, null, $this->silver);

        try {
            app(RecordEnrollmentPayment::class)($enrollment->fresh(), $this->organizer, PaymentChannelType::CASH, 65000);
            $this->fail('The enrollment came back next to another one of the same driver.');
        } catch (ValidationException) {
            $this->assertSame(EnrollmentStatus::Withdrawn, $enrollment->fresh()->status);
            $this->assertSame(30000, $enrollment->fresh()->paidAmount());
        }
    }

    public function test_a_withdrawn_enrollment_comes_back_with_the_licence_emitted_meanwhile(): void
    {
        $renewal = LicenceRequest::factory()->withLicenceAndHolder()->create();
        $enrollment = Enrollment::factory()->for($this->championship)->waitingFor($renewal)->create(['enrollment_option_id' => $this->gold->getKey(), 'amount' => 95000, 'deposit_amount' => 30000, 'payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);
        app(WithdrawEnrollment::class)($enrollment);

        $licence = Licence::factory()->for($renewal->user)->create(['validity_year' => $this->championship->start_at->year, 'licence_request_id' => $renewal->getKey()]);
        $renewal->update(['status' => LicenceRequestStatus::Emitted, 'licence_id' => $licence->getKey()]);

        $this->actingAs($this->organizer)
            ->post(route('enrollments.payment.record', $enrollment), ['channel' => PaymentChannelType::CASH->value, 'amount' => '300'])
            ->assertSessionHasNoErrors();

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->status);
        $this->assertTrue($enrollment->licence->is($licence));
        $this->assertNull($enrollment->licence_request_id);
    }

    public function test_a_deposit_with_no_race_fee_discounted_becomes_a_promo_code_of_the_same_amount(): void
    {
        $enrollment = $this->enrollWith($this->gold, paid: 30000);
        app(WithdrawEnrollment::class)($enrollment);

        $promoCode = app(CreditEnrollmentPayment::class)($enrollment->fresh(), $this->organizer);

        $this->assertSame(EntitlementMode::Balance, $promoCode->mode);
        $this->assertSame(30000, $promoCode->amount);
        $this->assertSame(PromoCodeStatus::Unclaimed, $promoCode->status);
        $this->assertTrue($enrollment->fresh()->creditPromoCode->is($promoCode));
    }

    public function test_a_licence_renewal_the_enrollment_covered_is_deducted_from_the_credit(): void
    {
        $this->gold->update(['covers_licence_renewal' => true]);
        $renewal = LicenceRequest::factory()->withLicenceAndHolder()->create();
        $enrollment = Enrollment::factory()->for($this->championship)->waitingFor($renewal)->create(['enrollment_option_id' => $this->gold->getKey(), 'amount' => 95000, 'deposit_amount' => 30000, 'payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);
        $renewal->coverWith($enrollment);
        app(RecordEnrollmentPayment::class)($enrollment, $this->organizer, PaymentChannelType::CASH, 30000);
        $licence = Licence::factory()->for($renewal->user)->create(['validity_year' => $this->championship->start_at->year, 'licence_request_id' => $renewal->getKey()]);
        $renewal->update(['status' => LicenceRequestStatus::Emitted, 'licence_id' => $licence->getKey()]);
        $enrollment->update(['licence_id' => $licence->getKey(), 'licence_request_id' => null]);
        app(WithdrawEnrollment::class)($enrollment->fresh());
        config(['licences.renewal_price' => '50']);

        $this->actingAs($this->organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSee('name="renewal_deduction"', false)
            ->assertSee('value="50.00"', false);

        $this->actingAs($this->organizer)
            ->post(route('enrollments.credit', $enrollment), ['renewal_deduction' => '50'])
            ->assertSessionHasNoErrors();

        $this->assertSame(25000, $enrollment->fresh()->creditPromoCode->amount);
    }

    public function test_a_rejected_enrollment_with_payments_is_credited_too(): void
    {
        $enrollment = $this->enrollWith($this->gold, paid: 30000);
        app(RejectEnrollment::class)($enrollment, 'Not eligible.');

        $promoCode = app(CreditEnrollmentPayment::class)($enrollment->fresh(), $this->organizer);

        $this->assertSame(30000, $promoCode->amount);
    }

    public function test_the_organizer_brings_back_a_withdrawn_enrollment_already_paid_in_full(): void
    {
        $enrollment = $this->enrollWith($this->gold, paid: 95000);
        app(WithdrawEnrollment::class)($enrollment);

        $this->actingAs($this->organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSee(route('enrollments.bring-back', $enrollment));

        $this->actingAs($this->organizer)
            ->post(route('enrollments.bring-back', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);
        $this->assertSame(EnrollmentPaymentStatus::Paid, $enrollment->fresh()->payment_status);
    }

    public function test_the_race_fee_discount_waits_for_the_deposit_of_the_new_option(): void
    {
        $this->silver->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 4]);
        $this->gold->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 6]);
        $enrollment = $this->enrollWith($this->silver, paid: 20000);
        $credits = Entitlement::query()->where('source_id', $enrollment->getKey())->sole();

        app(ChangeEnrollmentOption::class)($enrollment, $this->gold);

        $this->assertSame(0, $credits->fresh()->remaining);

        app(RecordEnrollmentPayment::class)($enrollment->fresh(), $this->organizer, PaymentChannelType::CASH, 10000);

        $this->assertSame(6, $credits->fresh()->amount);
        $this->assertSame(6, $credits->fresh()->remaining);
    }

    public function test_an_option_without_a_race_fee_discount_cancels_the_unused_one(): void
    {
        $this->silver->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 4]);
        $enrollment = $this->enrollWith($this->silver, paid: 30000);

        app(ChangeEnrollmentOption::class)($enrollment, $this->gold);

        $this->assertSame(0, Entitlement::query()->where('source_id', $enrollment->getKey())->sole()->remaining);
    }

    public function test_bringing_back_grants_the_race_fee_discount_on_the_licence_emitted_meanwhile(): void
    {
        $this->gold->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 6]);
        $renewal = LicenceRequest::factory()->withLicenceAndHolder()->create();
        $enrollment = Enrollment::factory()->for($this->championship)->waitingFor($renewal)->create(['enrollment_option_id' => $this->gold->getKey(), 'amount' => 95000, 'deposit_amount' => 30000, 'payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);
        app(RecordEnrollmentPayment::class)($enrollment, $this->organizer, PaymentChannelType::CASH, 30000);
        app(WithdrawEnrollment::class)($enrollment->fresh());

        $licence = Licence::factory()->for($renewal->user)->emitted()->create(['validity_year' => $this->championship->start_at->year, 'licence_request_id' => $renewal->getKey()]);
        $renewal->update(['status' => LicenceRequestStatus::Emitted, 'licence_id' => $licence->getKey()]);

        app(BringBackEnrollment::class)($enrollment->fresh());

        $this->assertSame(6, Entitlement::query()->where('source_id', $enrollment->getKey())->sole()->remaining);
    }

    public function test_a_deduction_beyond_what_was_paid_issues_no_promo_code(): void
    {
        $this->gold->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 6]);
        $enrollment = $this->enrollWith($this->gold, paid: 30000);
        $credits = Entitlement::query()->where('source_id', $enrollment->getKey())->sole();
        $this->useCredit($credits, 40000);
        app(WithdrawEnrollment::class)($enrollment);

        $this->assertNull(app(CreditEnrollmentPayment::class)($enrollment->fresh(), $this->organizer));
        $this->assertNotNull($enrollment->fresh()->credited_at);
        $this->assertSame(0, $credits->fresh()->remaining);
    }

    public function test_a_rejected_enrollment_without_a_licence_is_credited_but_never_brought_back(): void
    {
        $renewal = LicenceRequest::factory()->withLicenceAndHolder()->create();
        $enrollment = Enrollment::factory()->for($this->championship)->waitingFor($renewal)->create(['enrollment_option_id' => $this->gold->getKey(), 'amount' => 95000, 'deposit_amount' => 30000, 'payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);
        app(RecordEnrollmentPayment::class)($enrollment, $this->organizer, PaymentChannelType::CASH, 30000);
        app(RejectEnrollment::class)($enrollment->fresh(), 'Not eligible.');

        $this->actingAs($this->organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertDontSee(route('enrollments.bring-back', $enrollment));

        $this->actingAs($this->organizer)
            ->post(route('enrollments.bring-back', $enrollment))
            ->assertSessionHasErrors('payment');

        $promoCode = app(CreditEnrollmentPayment::class)($enrollment->fresh(), $this->organizer);

        $this->assertSame(30000, $promoCode->amount);
        $this->assertTrue($promoCode->championship->is($this->championship));
        $this->assertSame($this->organizer->getKey(), $promoCode->created_by);
    }

    public function test_the_race_fee_discount_follows_the_new_option(): void
    {
        $this->silver->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 4]);
        $this->gold->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 6]);
        $enrollment = $this->enrollWith($this->silver, paid: 30000);
        $credits = Entitlement::query()->where('source_id', $enrollment->getKey())->sole();
        $this->useCredit($credits, 5000);

        app(ChangeEnrollmentOption::class)($enrollment, $this->gold);

        $credits->refresh();
        $this->assertSame(6, $credits->amount);
        $this->assertSame(5, $credits->remaining);

        $this->gold->update(['entitlement_mode' => EntitlementMode::Balance, 'entitlement_amount' => 20000]);
        app(ChangeEnrollmentOption::class)($enrollment->fresh(), $this->silver->fresh());
        app(ChangeEnrollmentOption::class)($enrollment->fresh(), $this->gold->fresh());

        $this->assertSame(0, $credits->fresh()->remaining);
        $this->assertSame(20000, Entitlement::query()->where('source_id', $enrollment->getKey())->where('mode', EntitlementMode::Balance)->sole()->remaining);
    }

    public function test_the_organizer_turns_a_withdrawn_deposit_into_a_promo_code(): void
    {
        $this->gold->update(['entitlement_mode' => EntitlementMode::Credit, 'entitlement_amount' => 6]);
        $enrollment = $this->enrollWith($this->gold, paid: 30000);
        $raceCredits = Entitlement::query()->where('source_id', $enrollment->getKey())->sole();
        $this->useCredit($raceCredits, 5000);
        $this->useCredit($raceCredits, 5000);
        app(WithdrawEnrollment::class)($enrollment);

        $this->actingAs($this->organizer)
            ->post(route('enrollments.credit', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(0, $raceCredits->fresh()->remaining);

        $promoCode = $enrollment->fresh()->creditPromoCode;
        $this->assertSame(EntitlementMode::Balance, $promoCode->mode);
        $this->assertSame(20000, $promoCode->amount);
        $this->assertNotNull($enrollment->fresh()->credited_at);

        $this->actingAs($this->organizer)
            ->get(route('enrollments.show', $enrollment))
            ->assertSee($promoCode->code);

        try {
            app(RecordEnrollmentPayment::class)($enrollment->fresh(), $this->organizer, PaymentChannelType::CASH, 65000);
            $this->fail('A credited enrollment came back.');
        } catch (ValidationException) {
            $this->assertSame(EnrollmentStatus::Withdrawn, $enrollment->fresh()->status);
        }

        $this->expectException(ValidationException::class);

        app(CreditEnrollmentPayment::class)($enrollment->fresh(), $this->organizer);
    }

    public function test_nobody_enrolls_a_new_licence_after_the_window_closes(): void
    {
        $this->closeTheWindow();
        $licence = Licence::factory()->verified()->create(['validity_year' => $this->championship->start_at->year]);

        $this->actingAs($this->organizer)
            ->get(route('championships.enrollments.index', $this->championship))
            ->assertDontSee(route('championships.enrollments.create', $this->championship));

        $this->actingAs($this->organizer)
            ->get(route('championships.enrollments.create', $this->championship))
            ->assertForbidden();

        $this->actingAs($this->organizer)
            ->post(route('championships.enrollments.store', $this->championship), ['licence' => $licence->ulid])
            ->assertForbidden();

        $this->assertSame(0, $this->championship->enrollments()->count());
    }

    private function option(string $title, int $amount, int $deposit): EnrollmentOption
    {
        return EnrollmentOption::factory()->for($this->championship)->create([
            'title' => $title,
            'amount' => $amount,
            'deposit_amount' => $deposit,
            'balance_due_on' => CarbonImmutable::parse('2027-03-31'),
        ]);
    }

    private function enrollWith(EnrollmentOption $option, int $paid): Enrollment
    {
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $this->championship->start_at->year]);
        $enrollment = app(CreateEnrollment::class)($user, $this->championship, $licence, null, null, $option);

        app(RecordEnrollmentPayment::class)($enrollment, $this->organizer, PaymentChannelType::CASH, $paid);

        return $enrollment->fresh();
    }

    private function closeTheWindow(): void
    {
        $this->championship->enrollment->closes_at = now()->subDay();
        $this->championship->save();
    }

    /**
     * A race registration of the credits' Licence that one credit discounted by the given race fee.
     */
    private function useCredit(Entitlement $entitlement, int $raceFee): void
    {
        $participant = Participant::factory()->recycle($this->championship)->create(['driver_licence' => $entitlement->licence_hash]);

        $participant->entitlements()->attach($entitlement, ['amount' => $entitlement->consume($raceFee)]);
    }
}
