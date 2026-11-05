<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\SubmitEnrollment;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public page of a championship that drivers can enroll in.
 */
class PublicChampionshipEnrollmentController extends Controller
{
    /**
     * The enrollment period and price, and for a signed-in User their Enrollments and the form to enroll.
     */
    public function show(Request $request, Championship $championship): View
    {
        $settings = $championship->enrollment;

        abort_unless($settings->enabled, 404);

        $user = $request->user();

        return view('public-championship.enrollment', [
            'championship' => $championship,
            'settings' => $settings,
            'price' => $this->feePrice($championship),
            'packages' => Product::query()->enrollingIn($championship)->with(['prices', 'grants'])->orderBy('title')->get()->filter(fn (Product $package) => $package->orderablePrice() !== null)->values(),
            'myEnrollments' => $user === null ? collect() : $championship->enrollments()
                ->where('user_id', $user->getKey())
                ->with(['licence', 'licenceRequest', 'order', 'reservations'])
                ->latest()
                ->get(),
            'licences' => $user === null ? collect() : Licence::query()->managedBy($user)->enrollableIn($championship)->orderBy('id')->get(),
            'licenceRequests' => $user === null ? collect() : LicenceRequest::query()->requestedBy($user)->enrollableIn($championship)->latest()->get(),
            'competitorLicences' => $user === null ? collect() : Licence::query()->managedBy($user)->competitorIn($championship)->orderBy('id')->get(),
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
            'competitor_licence' => $request->input('competitor_licence'),
            'product' => $request->input('product'),
            'bib' => $request->input('bib'),
        ]);

        if ($enrollment->order !== null) {
            return to_route('my-orders.show', $enrollment->order)
                ->with('flash.banner', __('Enrollment submitted. Pay for it to complete it.'));
        }

        return to_route('public.championships.enrollment', $championship)
            ->with('flash.banner', __('Enrollment submitted. The organizer will confirm it.'));
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
