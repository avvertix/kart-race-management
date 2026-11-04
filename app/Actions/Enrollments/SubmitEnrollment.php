<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitEnrollment
{
    public function __construct(private CreateEnrollment $createEnrollment) {}

    /**
     * The driver enrolls one of their Licences, or an open Licence Request, while enrollment is open. Places the Order
     * for the enrollment Product when not free.
     *
     * @param  array{licence?: ?string, licence_request?: ?string, competitor_licence?: ?string}  $input  ULIDs
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
        ])->validate();

        $licence = Licence::findByUlid($validated['licence'] ?? null);
        $licenceRequest = isset($validated['licence_request']) ? LicenceRequest::query()->where('ulid', $validated['licence_request'])->sole() : null;
        $competitorLicence = Licence::findByUlid($validated['competitor_licence'] ?? null);

        $holder = $licence->holder ?? $licenceRequest?->holder;

        if ($competitorLicence === null && $holder !== null && $championship->requiresCompetitorFor($holder)) {
            throw ValidationException::withMessages(['competitor_licence' => __('The driver is underage: name the competitor.')]);
        }

        return ($this->createEnrollment)($user, $championship, $licence, $licenceRequest, $competitorLicence, pays: true);
    }
}
