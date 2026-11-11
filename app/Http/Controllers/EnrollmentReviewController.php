<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\ChangeEnrollmentOption;
use App\Actions\Enrollments\ConfirmEnrollment;
use App\Actions\Enrollments\CreditEnrollmentPayment;
use App\Actions\Enrollments\FindPreviousNumbers;
use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\Enrollments\RecordEnrollmentPayment;
use App\Actions\Enrollments\RejectEnrollment;
use App\Actions\Enrollments\ReviewEnrollmentPayment;
use App\Actions\Enrollments\SetEnrollmentCompetitor;
use App\Actions\Enrollments\WithdrawEnrollment;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\PaymentChannelType;
use App\Support\RaceNumberAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The organizer reviews an Enrollment: checks what is missing, verifies a declared Licence, then confirms, rejects or
 * withdraws it.
 */
class EnrollmentReviewController extends Controller
{
    /**
     * The Enrollment with what is still missing before it can be confirmed, and the numbers its driver used before.
     */
    public function show(Enrollment $enrollment, FindPreviousNumbers $findPreviousNumbers): View
    {
        $this->authorize('review', Enrollment::class);

        $enrollment->load(['championship', 'licence', 'licenceRequest', 'competitorLicence', 'user', 'option', 'payments', 'reservations']);

        return view('enrollment.show', [
            'enrollment' => $enrollment,
            'missing' => $enrollment->missingForConfirmation(),
            'previousNumbers' => $findPreviousNumbers($enrollment),
            'availability' => RaceNumberAvailability::in($enrollment->championship, array_filter([$enrollment->licence?->number_hash]), $enrollment),
            'canPickNumber' => $enrollment->status === EnrollmentStatus::Submitted && $enrollment->grantsBibReservation(),
            'options' => $enrollment->championship->enrollmentOptions()->get(),
            'competitorLicences' => $enrollment->status === EnrollmentStatus::Submitted ? Enrollment::competitorCandidates($enrollment->championship, null, $enrollment->holder())->push(...array_filter([$enrollment->competitorLicence]))->unique('id') : collect(),
        ]);
    }

    /**
     * Confirm the Enrollment's payment proof with the amount received, in euro, without leaving the review.
     */
    public function confirmPayment(Request $request, Enrollment $enrollment, ReviewEnrollmentPayment $reviewEnrollmentPayment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $reviewEnrollmentPayment($enrollment, $request->user(), confirm: true, amount: $this->validatedAmountInCents($request));

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Payment confirmed.'));
    }

    /**
     * Reject the Enrollment's payment proof, so the driver uploads a new one, without leaving the review.
     */
    public function rejectPayment(Request $request, Enrollment $enrollment, ReviewEnrollmentPayment $reviewEnrollmentPayment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $reviewEnrollmentPayment($enrollment, $request->user(), confirm: false);

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Payment rejected.'));
    }

    /**
     * Record a payment received by cash, credit card or bank transfer without a proof, without leaving the review.
     */
    public function recordPayment(Request $request, Enrollment $enrollment, RecordEnrollmentPayment $recordEnrollmentPayment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $validated = $request->validate([
            'channel' => ['required', Rule::enum(PaymentChannelType::class)],
        ]);

        $recordEnrollmentPayment($enrollment, $request->user(), PaymentChannelType::from((int) $validated['channel']), $this->validatedAmountInCents($request));

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Payment recorded.'));
    }

    /**
     * Turn what the withdrawn Enrollment paid into a race fee balance on its Licence, see CreditEnrollmentPayment.
     */
    public function credit(Enrollment $enrollment, CreditEnrollmentPayment $creditEnrollmentPayment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $credit = $creditEnrollmentPayment($enrollment);

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', $credit === null
                ? __('Nothing left to credit: the race fees already discounted cover what was paid.')
                : __('Race fee balance of :amount given to the licence.', ['amount' => $credit->mode->describe($credit->amount)]));
    }

    /**
     * Move the Enrollment to another option of the championship until it is paid in full, also once enrollment closed.
     */
    public function option(Request $request, Enrollment $enrollment, ChangeEnrollmentOption $changeEnrollmentOption): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $option = $enrollment->championship->enrollmentOptions()->where('ulid', (string) $request->input('option'))->first();

        if ($option === null) {
            throw ValidationException::withMessages(['option' => __('The enrollment option is not available.')]);
        }

        $changeEnrollmentOption($enrollment, $option);

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Enrollment option changed.'));
    }

    /**
     * Name, change or remove the Competitor while the Enrollment is submitted, from any Licence that can be it.
     */
    public function competitor(Request $request, Enrollment $enrollment, SetEnrollmentCompetitor $setEnrollmentCompetitor): RedirectResponse
    {
        $this->authorize('changeCompetitor', $enrollment);

        $setEnrollmentCompetitor($enrollment, $request->input('competitor_licence'), among: null);

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Competitor of the enrollment saved.'));
    }

    /**
     * Verify the declared Licence of the Enrollment, without leaving the review.
     */
    public function verifyLicence(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $licence = $enrollment->licence;

        abort_if($licence === null, 404);

        $this->authorize('verify', $licence);

        $licence->markVerifiedBy($request->user());

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Licence verified.'));
    }

    /**
     * Pick or change the race number for the driver, while the Enrollment is submitted, also once confirmed on its own,
     * see PickRaceNumber.
     */
    public function raceNumber(Request $request, Enrollment $enrollment, PickRaceNumber $pickRaceNumber): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $reservation = $pickRaceNumber($enrollment, $request->input('bib'), $request->user(), byOrganizer: true);

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Race number :bib picked for the enrollment.', ['bib' => $reservation->bib]));
    }

    /**
     * Confirm the Enrollment, see ConfirmEnrollment.
     */
    public function confirm(Request $request, Enrollment $enrollment, ConfirmEnrollment $confirmEnrollment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $confirmEnrollment($enrollment, $request->user());

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Enrollment confirmed.'));
    }

    /**
     * Reject the Enrollment with a reason, see RejectEnrollment.
     */
    public function reject(Request $request, Enrollment $enrollment, RejectEnrollment $rejectEnrollment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $rejectEnrollment($enrollment, (string) $request->input('reason'));

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Enrollment rejected.'));
    }

    /**
     * Withdraw the submitted Enrollment, see WithdrawEnrollment.
     */
    public function withdraw(Enrollment $enrollment, WithdrawEnrollment $withdrawEnrollment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $withdrawEnrollment($enrollment);

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Enrollment withdrawn.'));
    }

    /**
     * The amount received, entered in euro with a decimal point or comma, in cents.
     */
    private function validatedAmountInCents(Request $request): int
    {
        $request->merge(['amount' => str_replace(',', '.', mb_trim((string) $request->input('amount')))]);

        $validated = $request->validate(
            ['amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000']],
            [],
            ['amount' => __('Amount (€)')],
        );

        return (int) round(((float) $validated['amount']) * 100);
    }
}
