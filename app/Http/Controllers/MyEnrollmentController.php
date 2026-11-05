<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\ChangeEnrollmentLicence;
use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\Enrollments\SetEnrollmentCompetitor;
use App\Actions\Enrollments\WithdrawEnrollment;
use App\Actions\SubmitPaymentProof;
use App\Enums\LicenceRequestStatus;
use App\Models\Enrollment;
use App\Support\EnrollmentSubjects;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MyEnrollmentController extends Controller
{
    /**
     * The Enrollment of the signed-in User: what is enrolled, the race number, and its payment with the proof upload.
     */
    public function show(Enrollment $enrollment): View
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['championship', 'licence', 'licenceRequest', 'competitorLicence', 'reservations', 'option', 'latestPayment']);

        return view('my-enrollment.show', [
            'enrollment' => $enrollment,
            'championship' => $enrollment->championship,
            'subjects' => Gate::allows('changeLicence', $enrollment) ? EnrollmentSubjects::for($enrollment->user, $enrollment->championship) : collect(),
            'competitorLicences' => Gate::allows('changeCompetitor', $enrollment) ? Enrollment::competitorCandidates($enrollment->championship, $enrollment->user, $enrollment->holder())->push(...array_filter([$enrollment->competitorLicence]))->unique('id') : collect(),
        ]);
    }

    /**
     * Name, change or remove the Competitor while the Enrollment is submitted, see SetEnrollmentCompetitor.
     */
    public function updateCompetitor(Request $request, Enrollment $enrollment, SetEnrollmentCompetitor $setEnrollmentCompetitor): RedirectResponse
    {
        $this->authorize('changeCompetitor', $enrollment);

        $setEnrollmentCompetitor($enrollment, $request->input('competitor_licence'), $enrollment->user);

        return to_route('my-enrollments.show', $enrollment)
            ->with('flash.banner', __('Competitor of the enrollment saved.'));
    }

    /**
     * Give the Enrollment another licence once its request was rejected, see ChangeEnrollmentLicence. The `subject` input
     * is `licence:<ulid>`, `licence_request:<ulid>` or `renew:<ulid>`.
     */
    public function updateLicence(Request $request, Enrollment $enrollment, ChangeEnrollmentLicence $changeEnrollmentLicence): RedirectResponse
    {
        $this->authorize('changeLicence', $enrollment);

        [$kind, $ulid] = array_pad(explode(':', (string) $request->input('subject'), 2), 2, null);

        $enrollment = $changeEnrollmentLicence($enrollment, $request->user(), [
            'licence' => $kind === 'licence' ? $ulid : null,
            'licence_request' => $kind === 'licence_request' ? $ulid : null,
            'renew' => $kind === 'renew' ? $ulid : null,
        ]);

        if ($enrollment->licenceRequest?->status === LicenceRequestStatus::Draft) {
            return to_route('licence-requests.edit', $enrollment->licenceRequest)
                ->with('flash.banner', __('Complete the licence request: the enrollment waits for it.'));
        }

        return to_route('my-enrollments.show', $enrollment)
            ->with('flash.banner', __('Licence of the enrollment changed.'));
    }

    /**
     * Upload the proof for the Enrollment's payment.
     */
    public function pay(Request $request, Enrollment $enrollment, SubmitPaymentProof $submitPaymentProof): RedirectResponse
    {
        $this->authorize('pay', $enrollment);

        $request->validate([
            'proof' => 'required|file',
        ]);

        $submitPaymentProof($enrollment, $request->file('proof'));

        return to_route('my-enrollments.show', $enrollment)
            ->with('flash.banner', __('Payment proof uploaded. The organizer will confirm it.'));
    }

    /**
     * The race number picker, to change the number while the enrollment is submitted.
     */
    public function editRaceNumber(Enrollment $enrollment): View
    {
        $this->authorize('pickRaceNumber', $enrollment);

        return view('my-enrollment.race-number', [
            'enrollment' => $enrollment,
            'championship' => $enrollment->championship,
        ]);
    }

    /**
     * Pick or change the race number, see PickRaceNumber.
     */
    public function updateRaceNumber(Request $request, Enrollment $enrollment, PickRaceNumber $pickRaceNumber): RedirectResponse
    {
        $this->authorize('pickRaceNumber', $enrollment);

        $reservation = $pickRaceNumber($enrollment, $request->input('bib'), $request->user());

        return to_route('my-enrollments.show', $enrollment)
            ->with('flash.banner', __('Race number :bib held for you until the organizer confirms the enrollment.', ['bib' => $reservation->bib]));
    }

    /**
     * The driver withdraws their Enrollment while it is submitted.
     */
    public function withdraw(Enrollment $enrollment, WithdrawEnrollment $withdrawEnrollment): RedirectResponse
    {
        $this->authorize('withdraw', $enrollment);

        $withdrawEnrollment($enrollment, byOrganizer: false);

        return to_route('my-enrollments.show', $enrollment)
            ->with('flash.banner', __('Enrollment withdrawn.'));
    }
}
