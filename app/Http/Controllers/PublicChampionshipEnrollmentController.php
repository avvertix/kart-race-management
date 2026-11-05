<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\SubmitEnrollment;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Licence;
use App\Models\User;
use App\Support\EnrollmentSubjects;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The public page of a championship that drivers can enroll in.
 */
class PublicChampionshipEnrollmentController extends Controller
{
    /**
     * The enrollment period and price, and for a signed-in User their Enrollments and the stepped form to enroll: what
     * to enroll, the Competitor when underage, what to pay, the race number when reserved, and a review.
     */
    public function show(Request $request, Championship $championship): View
    {
        $settings = $championship->enrollment;

        abort_unless($settings->enabled, 404);

        $user = $request->user();

        $options = $championship->enrollmentOptions()->offered()->get();

        return view('public-championship.enrollment', [
            'championship' => $championship,
            'settings' => $settings,
            'myEnrollments' => $user === null ? collect() : $championship->enrollments()
                ->where('user_id', $user->getKey())
                ->with(['licence', 'licenceRequest', 'order', 'reservations'])
                ->latest()
                ->get(),
            'subjects' => $user === null ? collect() : EnrollmentSubjects::for($user, $championship),
            'paymentOptions' => $this->paymentOptions($options),
            'options' => $options,
            'competitorLicences' => $user === null ? collect() : Enrollment::competitorCandidates($championship, $user, null),
        ]);
    }

    /**
     * Submit the Enrollment, then pay for it when not free. The `subject` input names what is enrolled, as
     * `licence:<ulid>` or `licence_request:<ulid>`.
     */
    public function store(Request $request, Championship $championship, SubmitEnrollment $submitEnrollment): RedirectResponse
    {
        abort_unless($championship->enrollment->enabled, 404);

        $this->authorize('create', Enrollment::class);

        [$kind, $ulid] = array_pad(explode(':', (string) $request->input('subject'), 2), 2, null);

        $enrollment = $submitEnrollment($request->user(), $championship, [
            'licence' => $kind === 'licence' ? $ulid : null,
            'licence_request' => $kind === 'licence_request' ? $ulid : null,
            'renew' => $kind === 'renew' ? $ulid : null,
            'competitor_licence' => $request->input('competitor_licence'),
            'option' => $request->input('option'),
            'bib' => $request->input('bib'),
        ]);

        if ($enrollment->licenceRequest?->status === LicenceRequestStatus::Draft) {
            return to_route('licence-requests.edit', $enrollment->licenceRequest)
                ->with('flash.banner', __('Enrollment submitted. Complete the licence request: the enrollment waits for it.'));
        }

        return to_route('my-enrollments.show', $enrollment)
            ->with('flash.banner', $enrollment->payment_status === EnrollmentPaymentStatus::AwaitingPayment
                ? __('Enrollment submitted. Pay for it to complete it.')
                : __('Enrollment submitted. The organizer will confirm it.'));
    }

    /**
     * The options offered to drivers, with whether each is paid, reserves a race number, and the licence categories it
     * is limited to (empty for any).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, EnrollmentOption>  $options
     * @return Collection<int, array{value: string, label: string, description: ?string, pays: bool, grantsNumber: bool, categories: array<int, int>}>
     */
    private function paymentOptions(\Illuminate\Database\Eloquent\Collection $options): Collection
    {
        return $options->toBase()->map(fn (EnrollmentOption $option) => [
            'value' => $option->ulid,
            'label' => $option->title.' · '.$option->amountLabel(),
            'description' => $option->description,
            'pays' => ! $option->isFree(),
            'grantsNumber' => $option->reserves_race_number,
            'categories' => $option->licence_categories ?? [],
        ])->values();
    }
}
