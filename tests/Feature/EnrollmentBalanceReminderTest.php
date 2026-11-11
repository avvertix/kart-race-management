<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\CreateEnrollment;
use App\Actions\Enrollments\RecordEnrollmentPayment;
use App\Actions\Enrollments\RejectEnrollment;
use App\Actions\Enrollments\ReviewEnrollmentPayment;
use App\Actions\Enrollments\WithdrawEnrollment;
use App\Actions\SubmitPaymentProof;
use App\Enums\EnrollmentStatus;
use App\Enums\NotificationType;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Licence;
use App\Models\PaymentChannelType;
use App\Models\User;
use App\Notifications\EnrollmentBalanceDue;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EnrollmentBalanceReminderTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_drivers_are_reminded_once_a_week_before_the_balance_is_due(): void
    {
        Notification::fake();

        $dueInAWeek = $this->enrollmentWithDepositPaid($this->today()->addDays(7));
        $dueToday = $this->enrollmentWithDepositPaid($this->today());
        $dueLater = $this->enrollmentWithDepositPaid($this->today()->addDays(8));
        $paidInFull = $this->enrollmentWithDepositPaid($this->today()->addDays(3), alsoPay: 65000);
        $withdrawn = $this->enrollmentWithDepositPaid($this->today()->addDays(3));
        app(WithdrawEnrollment::class)($withdrawn);
        $overdue = $this->enrollmentWithDepositPaid($this->today()->subDay());

        $this->artisan('enrollments:remind-balance')->assertSuccessful();
        $this->artisan('enrollments:remind-balance')->assertSuccessful();

        Notification::assertSentToTimes($dueInAWeek->user, EnrollmentBalanceDue::class, 1);
        Notification::assertSentToTimes($dueToday->user, EnrollmentBalanceDue::class, 1);

        foreach ([$dueLater, $paidInFull, $withdrawn, $overdue] as $enrollment) {
            Notification::assertNotSentTo($enrollment->user, EnrollmentBalanceDue::class);
        }

        $this->assertNotNull($dueInAWeek->fresh()->balance_reminded_at);
        $this->assertNull($dueLater->fresh()->balance_reminded_at);
    }

    public function test_a_proof_under_review_waits_for_the_organizer_before_the_reminder(): void
    {
        Notification::fake();
        Storage::fake('payments');

        $enrollment = $this->enrollmentWithDepositPaid($this->today()->addDays(5));
        app(SubmitPaymentProof::class)($enrollment, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        $this->artisan('enrollments:remind-balance')->assertSuccessful();

        Notification::assertNotSentTo($enrollment->user, EnrollmentBalanceDue::class);

        app(ReviewEnrollmentPayment::class)($enrollment, User::factory()->organizer()->create(), confirm: true, amount: 10000);

        $this->artisan('enrollments:remind-balance')->assertSuccessful();

        Notification::assertSentToTimes($enrollment->user, EnrollmentBalanceDue::class, 1);
    }

    public function test_rejected_enrollments_and_options_without_a_deposit_are_not_reminded(): void
    {
        Notification::fake();

        $rejected = $this->enrollmentWithDepositPaid($this->today()->addDays(3));
        app(RejectEnrollment::class)($rejected, 'Not eligible.');

        $championship = Championship::factory()->openForEnrollment(amount: 18000)->create();
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['validity_year' => $championship->start_at->year]);
        $withoutDeposit = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $championship->enrollmentOptions()->sole());

        $this->artisan('enrollments:remind-balance')->assertSuccessful();

        Notification::assertNotSentTo($rejected->user, EnrollmentBalanceDue::class);
        Notification::assertNotSentTo($withoutDeposit->user, EnrollmentBalanceDue::class);
    }

    public function test_the_reminder_tells_the_balance_and_the_date(): void
    {
        $enrollment = $this->enrollmentWithDepositPaid(CarbonImmutable::parse('2027-03-31'));

        $notification = new EnrollmentBalanceDue($enrollment);
        $notification->id = (string) Str::uuid();

        $mail = $notification->toMail($enrollment->user);

        $this->assertContains(__('Pay the balance of :balance by :date.', ['balance' => '€650.00', 'date' => '31 March 2027']), $mail->introLines);
    }

    public function test_the_reminder_email_can_be_turned_off_from_the_notification_settings(): void
    {
        $enrollment = $this->enrollmentWithDepositPaid($this->today()->addDays(7));
        $user = $enrollment->user;

        $this->actingAs($user)
            ->get(route('notifications.edit'))
            ->assertSee(NotificationType::EnrollmentBalanceDue->label());

        $user->update(['notification_preferences' => [NotificationType::EnrollmentBalanceDue->value => false]]);

        $notification = new EnrollmentBalanceDue($enrollment);

        $this->assertFalse($notification->shouldSend($user->fresh(), 'mail'));
        $this->assertTrue($notification->shouldSend($user->fresh(), 'database'));
    }

    public function test_the_organizer_sees_and_filters_overdue_enrollments_which_stay_active(): void
    {
        $overdue = $this->enrollmentWithDepositPaid($this->today()->subDay(), licenceNumber: 'O100001');
        $overdue->update(['status' => EnrollmentStatus::Confirmed]);
        $notYet = $this->enrollmentWithDepositPaid($this->today()->addDay(), championship: $overdue->championship, licenceNumber: 'N100001');
        $notYet->update(['status' => EnrollmentStatus::Confirmed]);
        $organizer = User::factory()->organizer()->create();

        $this->assertTrue($overdue->fresh()->isOverdue());
        $this->assertFalse($notYet->fresh()->isOverdue());
        $this->assertTrue($overdue->fresh()->status->isActive());

        $this->actingAs($organizer)
            ->get(route('championships.enrollments.index', [$overdue->championship, 'status' => EnrollmentStatus::Confirmed->value]))
            ->assertSeeInOrder(['O100001', __('Overdue'), 'N100001']);

        $this->actingAs($organizer)
            ->get(route('championships.enrollments.index', [$overdue->championship, 'overdue' => 1]))
            ->assertSee('O100001')
            ->assertDontSee('N100001');

        $this->actingAs($organizer)
            ->get(route('dashboard'))
            ->assertSee(trans_choice(':count overdue balance|:count overdue balances', 1, ['count' => 1]))
            ->assertSee(route('championships.enrollments.index', [$overdue->championship, 'overdue' => 1]), false);

        $this->actingAs($overdue->user)
            ->get(route('dashboard'))
            ->assertDontSee(trans_choice(':count overdue balance|:count overdue balances', 1, ['count' => 1]));
    }

    private function today(): CarbonImmutable
    {
        return now()->setTimezone(config('races.timezone'))->startOfDay();
    }

    /**
     * A Gold Enrollment (€950, deposit €300) with the deposit paid and the balance due on the given day.
     */
    private function enrollmentWithDepositPaid(CarbonImmutable $dueOn, int $alsoPay = 0, ?Championship $championship = null, ?string $licenceNumber = null): Enrollment
    {
        $championship ??= Championship::factory()->openForEnrollment()->create();
        $gold = EnrollmentOption::factory()->for($championship)->create([
            'title' => 'Gold',
            'amount' => 95000,
            'deposit_amount' => 30000,
            'balance_due_on' => $dueOn,
        ]);
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(array_filter(['validity_year' => $championship->start_at->year, 'number' => $licenceNumber]));
        $organizer = User::factory()->organizer()->create();

        $enrollment = app(CreateEnrollment::class)($user, $championship, $licence, null, null, $gold);
        app(RecordEnrollmentPayment::class)($enrollment, $organizer, PaymentChannelType::CASH, 30000);

        if ($alsoPay > 0) {
            app(RecordEnrollmentPayment::class)($enrollment->fresh(), $organizer, PaymentChannelType::CASH, $alsoPay);
        }

        return $enrollment->fresh();
    }
}
