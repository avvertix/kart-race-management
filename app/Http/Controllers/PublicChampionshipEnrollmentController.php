<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\SubmitEnrollment;
use App\Enums\LicenceRequestStatus;
use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductGrant;
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

        $packages = Product::query()
            ->where(fn ($query) => $query->enrollingIn($championship)->orWhere(fn ($query) => $query->enrollingWithRenewalIn($championship)))
            ->with(['prices', 'grants'])
            ->orderBy('title')
            ->get()
            ->filter(fn (Product $package) => $package->orderablePrice() !== null)
            ->values();

        return view('public-championship.enrollment', [
            'championship' => $championship,
            'settings' => $settings,
            'price' => $this->feePrice($championship),
            'packages' => $packages,
            'myEnrollments' => $user === null ? collect() : $championship->enrollments()
                ->where('user_id', $user->getKey())
                ->with(['licence', 'licenceRequest', 'order', 'reservations'])
                ->latest()
                ->get(),
            'subjects' => $user === null ? collect() : EnrollmentSubjects::for($user, $championship),
            'paymentOptions' => $this->paymentOptions($championship, $packages),
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
            'product' => $request->input('product'),
            'bib' => $request->input('bib'),
        ]);

        if ($enrollment->licenceRequest?->status === LicenceRequestStatus::Draft) {
            return to_route('licence-requests.edit', $enrollment->licenceRequest)
                ->with('flash.banner', __('Enrollment submitted. Complete the licence request: the enrollment waits for it.'));
        }

        return to_route('my-enrollments.show', $enrollment)
            ->with('flash.banner', $enrollment->order !== null
                ? __('Enrollment submitted. Pay for it to complete it.')
                : __('Enrollment submitted. The organizer will confirm it.'));
    }

    /**
     * What can be paid for the Enrollment: the fee, or free, then each Package including it, with whether it is paid,
     * reserves a race number, and is for an existing Licence or a renewal started now (`for`).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $packages
     * @return Collection<int, array{value: string, label: string, pays: bool, grantsNumber: bool, for: string}>
     */
    private function paymentOptions(Championship $championship, \Illuminate\Database\Eloquent\Collection $packages): Collection
    {
        $settings = $championship->enrollment;
        $price = $this->feePrice($championship);

        return collect([[
            'value' => '',
            'label' => __('The enrollment on its own').' · '.($settings->isFree() ? __('Free') : ($price?->label() ?? __('Not available at the moment'))),
            'pays' => ! $settings->isFree(),
            'grantsNumber' => $settings->grants_bib_reservation,
            'for' => 'any',
        ]])->concat($packages->map(fn (Product $package) => [
            'value' => $package->ulid,
            'label' => $package->title.' · '.$package->orderablePrice()?->label(),
            'pays' => true,
            'grantsNumber' => $settings->grants_bib_reservation || $package->grants->contains(fn (ProductGrant $grant) => $grant->kind === ProductGrantKind::Enrollment && $grant->championship_id === $championship->getKey() && $grant->grants_bib_reservation),
            'for' => $package->grants->contains('kind', ProductGrantKind::LicenceEmission) ? 'renewal' : 'licence',
        ]))->values();
    }

    /**
     * The fixed Price of the enrollment fee, null when not on sale or not a fixed amount.
     */
    private function feePrice(Championship $championship): ?Price
    {
        $price = $championship->enrollmentProduct()?->orderablePrice();

        return $price !== null && ! $price->isRange() ? $price : null;
    }
}
