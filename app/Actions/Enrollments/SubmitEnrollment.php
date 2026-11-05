<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitEnrollment
{
    public function __construct(
        private CreateEnrollment $createEnrollment,
        private PickRaceNumber $pickRaceNumber,
    ) {}

    /**
     * The driver enrolls one of their Licences, or an open Licence Request, while enrollment is open. Places the Order
     * for the enrollment Product when not free, and reserves the picked race number when the championship grants one,
     * unless the Licence already holds a reservation in the championship: it keeps that number.
     *
     * @param  array{licence?: ?string, licence_request?: ?string, competitor_licence?: ?string, product?: ?string, bib?: mixed}  $input  ULIDs and the race number
     *
     * @throws ValidationException
     */
    public function __invoke(User $user, Championship $championship, array $input): Enrollment
    {
        if (! $championship->enrollment->isOpen()) {
            throw ValidationException::withMessages(['enrollment' => __('Enrollment is closed.')]);
        }

        $validated = Validator::make($input, [
            'licence' => [
                'nullable',
                'required_without:licence_request',
                'prohibits:licence_request',
                Rule::in(Licence::query()->managedBy($user)->enrollableIn($championship)->pluck('ulid')),
            ],
            'licence_request' => [
                'nullable',
                'required_without:licence',
                Rule::in(LicenceRequest::query()->requestedBy($user)->enrollableIn($championship)->pluck('ulid')),
            ],
            'competitor_licence' => [
                'nullable',
                Rule::in(Licence::query()->managedBy($user)->competitorIn($championship)->pluck('ulid')),
            ],
            'product' => [
                'nullable',
                Rule::in(Product::query()->enrollingIn($championship)->pluck('ulid')),
            ],
        ])->validate();

        $package = isset($validated['product']) ? Product::query()->where('ulid', $validated['product'])->sole() : null;

        $licence = Licence::findByUlid($validated['licence'] ?? null);
        $licenceRequest = isset($validated['licence_request']) ? LicenceRequest::query()->where('ulid', $validated['licence_request'])->sole() : null;
        $competitorLicence = Licence::findByUlid($validated['competitor_licence'] ?? null);

        $holder = $licence->holder ?? $licenceRequest?->holder;

        if ($competitorLicence === null && $holder !== null && $championship->requiresCompetitorFor($holder)) {
            throw ValidationException::withMessages(['competitor_licence' => __('The driver is underage: name the competitor.')]);
        }

        $createEnrollment = fn () => ($this->createEnrollment)($user, $championship, $licence, $licenceRequest, $competitorLicence, pays: true, package: $package);

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
}
