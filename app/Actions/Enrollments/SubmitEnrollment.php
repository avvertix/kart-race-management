<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Actions\LicenceRequests\StartLicenceRenewal;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Support\EnrollmentSubjects;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitEnrollment
{
    /**
     * The value of `renew` that starts an empty renewal, for a licence that isn't on the platform.
     */
    public const RENEW_NEW = 'new';

    public function __construct(
        private CreateEnrollment $createEnrollment,
        private PickRaceNumber $pickRaceNumber,
        private StartLicenceRenewal $startLicenceRenewal,
        private GrantEnrollmentEntitlements $grantEnrollmentEntitlements,
    ) {}

    /**
     * The driver enrolls one of their Licences, an open Licence Request, or a renewal started now for the championship's
     * year, while enrollment is open, with an Enrollment Option offered for the licence category: its amount becomes the
     * Enrollment's payment, and an option covering the renewal pays the request started. A free option's race fee
     * discount is granted at once when the Licence is verified. Reserves the picked race number when the championship or the Package grants one,
     * unless the Licence already holds a reservation in the championship: it keeps that number.
     *
     * @param  array{licence?: ?string, licence_request?: ?string, renew?: ?string, competitor_licence?: ?string, option?: ?string, bib?: mixed}  $input  ULIDs, `new` to renew a licence not on the platform, and the race number
     *
     * @throws ValidationException
     */
    public function __invoke(User $user, Championship $championship, array $input): Enrollment
    {
        if (! $championship->enrollment->isOpen()) {
            throw ValidationException::withMessages(['enrollment' => __('Enrollment is closed.')]);
        }

        $renews = filled($input['renew'] ?? null);

        $validated = Validator::make($input, [
            ...self::subjectRules($user, $championship),
            'competitor_licence' => [
                'nullable',
                Rule::in(Licence::query()->managedBy($user)->competitorIn($championship)->pluck('ulid')),
            ],
            'option' => ['nullable', 'string'],
        ])->validate();

        $licence = Licence::findByUlid($validated['licence'] ?? null);
        $licenceRequest = isset($validated['licence_request']) ? LicenceRequest::query()->where('ulid', $validated['licence_request'])->sole() : null;
        $previous = $renews ? Licence::findByUlid($validated['renew'] === self::RENEW_NEW ? null : $validated['renew']) : null;
        $competitorLicence = Licence::findByUlid($validated['competitor_licence'] ?? null);

        $option = $this->chosenOption($championship, self::categoryOf($licence, $licenceRequest, $previous), $validated['option'] ?? null);

        $holder = $licence->holder ?? $licenceRequest->holder ?? $previous?->holder;

        if ($competitorLicence === null && $holder !== null && $championship->requiresCompetitorFor($holder)) {
            throw ValidationException::withMessages(['competitor_licence' => __('The driver is underage: name the competitor.')]);
        }

        $createEnrollment = fn () => DB::transaction(function () use ($user, $championship, $licence, $licenceRequest, $renews, $previous, $competitorLicence, $option) {
            $waitedFor = $renews
                ? ($this->startLicenceRenewal)($user, $previous, validityYear: $championship->start_at->year)
                : $licenceRequest;

            return ($this->createEnrollment)($user, $championship, $licence, $waitedFor, $competitorLicence, $option);
        });

        if (! $option->reserves_race_number
            || blank($input['bib'] ?? null)
            || PickRaceNumber::reservationHeldBy($championship, $licence) !== null) {
            $enrollment = $createEnrollment();
        } else {
            $bib = $this->pickRaceNumber->validated($input['bib']);

            $enrollment = $this->pickRaceNumber->whileHolding($bib, fn () => DB::transaction(function () use ($createEnrollment, $bib, $user) {
                $enrollment = $createEnrollment();

                $this->pickRaceNumber->assign($enrollment, $bib, $user);

                return $enrollment;
            }));
        }

        ($this->grantEnrollmentEntitlements)($enrollment);

        return $enrollment;
    }

    /**
     * The race licence category of what is enrolled: the Licence's, the requested Licence Type's, or the renewed
     * Licence's; null when not known yet, e.g. renewing a licence not on the platform.
     */
    public static function categoryOf(?Licence $licence, ?LicenceRequest $licenceRequest, ?Licence $previous): ?DriverLicence
    {
        return $licence->race_licence_category
            ?? $licenceRequest?->licenceTypeData()->raceLicenceCategory
            ?? $previous?->race_licence_category;
    }

    /**
     * The rules choosing what is enrolled, exactly one of: a Licence of the User, an open Licence Request of theirs, or
     * the renewal of a driver Licence they manage from an earlier year (`new` for a licence not on the platform).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function subjectRules(User $user, Championship $championship): array
    {
        return [
            'licence' => [
                'nullable',
                'required_without_all:licence_request,renew',
                'prohibits:licence_request,renew',
                Rule::in(Licence::query()->managedBy($user)->enrollableIn($championship)->pluck('ulid')),
            ],
            'licence_request' => [
                'nullable',
                'required_without_all:licence,renew',
                'prohibits:renew',
                Rule::in(LicenceRequest::query()->requestedBy($user)->enrollableIn($championship)->pluck('ulid')),
            ],
            'renew' => [
                'nullable',
                'required_without_all:licence,licence_request',
                Rule::in(EnrollmentSubjects::for($user, $championship)->where('kind', 'renewal')->map(fn (array $subject) => mb_substr($subject['value'], mb_strlen('renew:')))->all()),
            ],
        ];
    }

    /**
     * The Enrollment Option chosen among those offered to the licence category; the only one offered when none is
     * chosen.
     *
     * @throws ValidationException when no option is offered, or a choice among several is missing or not offered
     */
    private function chosenOption(Championship $championship, ?DriverLicence $category, ?string $ulid): EnrollmentOption
    {
        $offered = $championship->enrollmentOptions()->offeredFor($category)->get();

        if ($offered->isEmpty()) {
            throw ValidationException::withMessages(['option' => __('No enrollment option is available for this licence.')]);
        }

        if ($ulid === null && $offered->count() === 1) {
            return $offered->sole();
        }

        return $offered->firstWhere('ulid', $ulid)
            ?? throw ValidationException::withMessages(['option' => __('Choose one of the enrollment options offered for this licence.')]);
    }
}
