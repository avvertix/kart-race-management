<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Enrollments\CreateEnrollment;
use App\Enums\EnrollmentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The organizer lists the Enrollments of a championship and enrolls Licences on behalf of their drivers.
 */
class ChampionshipEnrollmentController extends Controller
{
    /**
     * The Enrollments of one status, submitted by default, oldest first.
     */
    public function index(Request $request, Championship $championship): View
    {
        $this->authorize('viewAny', Enrollment::class);

        abort_unless($championship->enrollment->enabled, 404);

        $request->validate([
            'status' => ['nullable', Rule::enum(EnrollmentStatus::class)],
        ]);

        $status = $request->enum('status', EnrollmentStatus::class) ?? EnrollmentStatus::Submitted;

        $enrollments = $championship->enrollments()
            ->with(['licence', 'licenceRequest', 'competitorLicence', 'order', 'reservations'])
            ->where('status', $status)
            ->oldest()
            ->oldest('id')
            ->paginate(50)
            ->withQueryString();

        $enrollments->each(fn (Enrollment $enrollment) => $enrollment->setRelation('championship', $championship));

        return view('enrollment.index', [
            'championship' => $championship,
            'status' => $status,
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * The form to enroll a Licence on behalf of its driver, at any time.
     */
    public function create(Championship $championship): View
    {
        $this->authorize('review', Enrollment::class);

        abort_unless($championship->enrollment->enabled, 404);

        return view('enrollment.create', [
            'championship' => $championship,
            'licences' => $this->enrollableLicences($championship)->orderBy('id')->get(),
            'competitorLicences' => Licence::query()->competitorIn($championship)->orderBy('id')->get(),
        ]);
    }

    /**
     * Enroll a Licence on behalf of its driver: the Enrollment belongs to the User managing the Licence, who sees and can
     * withdraw it. No Order is placed: the organizer settles the payment directly.
     */
    public function store(Request $request, Championship $championship, CreateEnrollment $createEnrollment): RedirectResponse
    {
        $this->authorize('review', Enrollment::class);

        abort_unless($championship->enrollment->enabled, 404);

        $validated = $request->validate([
            'licence' => ['required', 'string', Rule::in($this->enrollableLicences($championship)->pluck('ulid'))],
            'competitor_licence' => ['nullable', 'string', Rule::in(Licence::query()->competitorIn($championship)->pluck('ulid'))],
        ]);

        $licence = Licence::findByUlid($validated['licence']);

        $enrollment = $createEnrollment(
            $licence->user,
            $championship,
            $licence,
            null,
            Licence::findByUlid($validated['competitor_licence'] ?? null),
            pays: false,
        );

        return to_route('enrollments.show', $enrollment)
            ->with('flash.banner', __('Enrollment created. Confirm it when everything is in order.'));
    }

    /**
     * The driver Licences valid in the championship year, of its provider when set, not enrolled yet.
     *
     * @return Builder<Licence>
     */
    private function enrollableLicences(Championship $championship): Builder
    {
        return Licence::query()
            ->enrollableIn($championship)
            ->whereNotIn('id', $championship->enrollments()->active()->whereNotNull('licence_id')->select('licence_id'));
    }
}
