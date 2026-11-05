<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\Enrollments\WithdrawEnrollment;
use App\Actions\SubmitPaymentProof;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyEnrollmentController extends Controller
{
    /**
     * The Enrollment of the signed-in User: what is enrolled, the race number, and its payment with the proof upload.
     */
    public function show(Enrollment $enrollment): View
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['championship', 'licence', 'licenceRequest', 'competitorLicence', 'reservations', 'order.lines.price', 'order.latestPayment']);

        return view('my-enrollment.show', [
            'enrollment' => $enrollment,
            'championship' => $enrollment->championship,
        ]);
    }

    /**
     * Upload the proof for the Enrollment's payment.
     */
    public function pay(Request $request, Enrollment $enrollment, SubmitPaymentProof $submitPaymentProof): RedirectResponse
    {
        $this->authorize('view', $enrollment);

        abort_if($enrollment->order === null, 404);

        $this->authorize('pay', $enrollment->order);

        $request->validate([
            'proof' => 'required|file',
        ]);

        $submitPaymentProof($enrollment->order, $request->file('proof'));

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
