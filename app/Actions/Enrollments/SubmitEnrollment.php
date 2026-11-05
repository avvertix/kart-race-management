<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Actions\LicenceRequests\StartLicenceRenewal;
use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Product;
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
    ) {}

    /**
     * The driver enrolls one of their Licences, an open Licence Request, or a renewal started now for the championship's
     * year, while enrollment is open. Places the Order for the fee or the chosen Package when not free; a renewal can be
     * paid by a Package including it. Reserves the picked race number when the championship or the Package grants one,
     * unless the Licence already holds a reservation in the championship: it keeps that number.
     *
     * @param  array{licence?: ?string, licence_request?: ?string, renew?: ?string, competitor_licence?: ?string, product?: ?string, bib?: mixed}  $input  ULIDs, `new` to renew a licence not on the platform, and the race number
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
            'product' => [
                'nullable',
                Rule::in(($renews ? Product::query()->enrollingWithRenewalIn($championship) : Product::query()->enrollingIn($championship))->pluck('ulid')),
            ],
        ])->validate();

        $package = isset($validated['product']) ? Product::query()->where('ulid', $validated['product'])->sole() : null;

        $licence = Licence::findByUlid($validated['licence'] ?? null);
        $licenceRequest = isset($validated['licence_request']) ? LicenceRequest::query()->where('ulid', $validated['licence_request'])->sole() : null;
        $previous = $renews ? Licence::findByUlid($validated['renew'] === self::RENEW_NEW ? null : $validated['renew']) : null;
        $competitorLicence = Licence::findByUlid($validated['competitor_licence'] ?? null);

        $holder = $licence->holder ?? $licenceRequest->holder ?? $previous?->holder;

        if ($competitorLicence === null && $holder !== null && $championship->requiresCompetitorFor($holder)) {
            throw ValidationException::withMessages(['competitor_licence' => __('The driver is underage: name the competitor.')]);
        }

        $createEnrollment = fn () => DB::transaction(function () use ($user, $championship, $licence, $licenceRequest, $renews, $previous, $competitorLicence, $package) {
            $waitedFor = $renews
                ? ($this->startLicenceRenewal)($user, $previous, validityYear: $championship->start_at->year, provider: $championship->licences->provider)
                : $licenceRequest;

            return ($this->createEnrollment)($user, $championship, $licence, $waitedFor, $competitorLicence, pays: true, package: $package);
        });

        $packageGrantsNumber = $package !== null && $package->grants()
            ->where('kind', ProductGrantKind::Enrollment)
            ->where('championship_id', $championship->getKey())
            ->where('grants_bib_reservation', true)
            ->exists();

        if (! ($championship->enrollment->grants_bib_reservation || $packageGrantsNumber)
            || blank($input['bib'] ?? null)
            || PickRaceNumber::reservationHeldBy($championship, $licence) !== null) {
            return $createEnrollment();
        }

        $bib = $this->pickRaceNumber->validated($input['bib']);

        return $this->pickRaceNumber->whileHolding($bib, fn () => DB::transaction(function () use ($createEnrollment, $bib, $user) {
            $enrollment = $createEnrollment();

            $this->pickRaceNumber->assign($enrollment, $bib, $user);

            return $enrollment;
        }));
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
}
