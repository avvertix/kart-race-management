<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\ConfirmEnrollment;
use App\Actions\Enrollments\RejectEnrollment;
use App\Actions\Enrollments\WithdrawEnrollment;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The organizer reviews an Enrollment: checks what is missing, verifies a declared Licence, then confirms, rejects or
 * withdraws it.
 */
class EnrollmentReviewController extends Controller
{
    /**
     * The Enrollment with what is still missing before it can be confirmed.
     */
    public function show(Enrollment $enrollment): View
    {
        $this->authorize('review', Enrollment::class);

        $enrollment->load(['championship', 'licence', 'licenceRequest', 'competitorLicence', 'user', 'order', 'reservations']);

        return view('enrollment.show', [
            'enrollment' => $enrollment,
            'missing' => $enrollment->missingForConfirmation(),
        ]);
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
     * Withdraw the Enrollment, also once confirmed, see WithdrawEnrollment.
     */
    public function withdraw(Enrollment $enrollment, WithdrawEnrollment $withdrawEnrollment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        $withdrawEnrollment($enrollment, byOrganizer: true);

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Enrollment withdrawn.'));
    }
}
