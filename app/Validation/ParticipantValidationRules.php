<?php

declare(strict_types=1);

namespace App\Validation;

use App\Models\BibReservation;
use App\Models\Category;
use App\Models\Championship;
use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RegistrationForm;
use App\Models\Sex;
use App\Models\User;
use App\Rules\DateFormat;
use App\Rules\FiscalCodeFormatRule;
use App\Rules\LicenseNumberValidationRule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Throwable;

trait ParticipantValidationRules
{
    protected function processAddressInput($input, $fieldPrefix)
    {
        return [
            'address' => $input[$fieldPrefix.'_address'] ?? null,
            'city' => $input[$fieldPrefix.'_city'] ?? null,
            'province' => $input[$fieldPrefix.'_province'] ?? null,
            'postal_code' => $input[$fieldPrefix.'_postal_code'] ?? null,
        ];
    }

    protected function processVehicle($input, ?Race $race = null)
    {
        if (! $this->useCompleteForm($race)) {
            return [];
        }

        return [[
            'chassis_manufacturer' => $input['vehicle_chassis_manufacturer'] ?? null,
            'chassis_model' => mb_strtolower($input['vehicle_chassis_model'] ?? ''),
            'chassis_homologation' => mb_strtolower($input['vehicle_chassis_homologation'] ?? ''),
            'chassis_number' => mb_strtolower($input['vehicle_chassis_number'] ?? ''),
            'engine_manufacturer' => mb_strtolower($input['vehicle_engine_manufacturer'] ?? ''),
            'engine_model' => mb_strtolower($input['vehicle_engine_model'] ?? ''),
            'engine_homologation' => mb_strtolower($input['vehicle_engine_homologation'] ?? ''),
            'engine_number' => mb_strtolower($input['vehicle_engine_number'] ?? ''),
            'oil_manufacturer' => $input['vehicle_oil_manufacturer'] ?? null,
            'oil_type' => $input['vehicle_oil_type'] ?? null,
            'oil_percentage' => $input['vehicle_oil_percentage'] ?? null,
        ]];
    }

    protected function getBibValidationRules(): array
    {
        return [
            'bib' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function getCategoryValidationRules(Championship|int $championship): array
    {
        $championship_key = $championship instanceof Championship ? $championship->getKey() : $championship;

        return [
            'category' => [
                'required',
                'string',
                'ulid',
                Rule::exists((new Category())->getTable(), 'ulid')->where(function ($query) use ($championship_key) {
                    return $query->where('championship_id', $championship_key)->where('enabled', true);
                }), ],
        ];
    }

    protected function getDriverValidationRules(?Race $race = null): array
    {
        $championship = $race?->championship;
        $acceptedDriverLicences = $championship?->licences->accepted_driver_licences ?? [];

        $driverLicenceTypeRules = ['required', new Enum(DriverLicence::class)];
        if (! empty($acceptedDriverLicences)) {
            $driverLicenceTypeRules[] = Rule::in($acceptedDriverLicences);
        }

        $rules = [
            'driver_licence_type' => $driverLicenceTypeRules,

            'driver_first_name' => ['required', 'string', 'max:250'],
            'driver_last_name' => ['required', 'string', 'max:250'],

            'driver_licence_number' => ['required', 'string', 'max:250', 'min:3', new LicenseNumberValidationRule],
            'driver_licence_renewed_at' => ['nullable'],
            'driver_nationality' => ['required', 'string', 'max:250'], // Impostare possibili nazionalità
            'driver_email' => ['required', 'string', 'email'],
            'driver_phone' => ['required', 'string'],
            'driver_birth_date' => ['required', 'string', new DateFormat],
            'driver_birth_place' => ['required', 'string'],
            'driver_medical_certificate_expiration_date' => ['required', 'string', new DateFormat],
            'driver_residence_address' => ['required', 'string'],
            'driver_sex' => ['required', new Enum(Sex::class)],
            'driver_residence_address' => ['required', 'string', 'max:250'],
            'driver_residence_city' => ['required', 'string',  'max:250'],
            'driver_residence_province' => ['nullable', 'string',  'max:250'],
            'driver_residence_postal_code' => ['required', 'string', 'max:250'],
            'driver_fiscal_code' => ['string', 'max:250', new FiscalCodeFormatRule(check_driver: true)], // indicare che obbligatorio se nazionalità = italia o licenza diversa da internazionale, validare formato codice fiscale italiano
            // in base ai dati forniti si può validare parte del codice fiscale
            // check spam, we do continue processing, we add spam score and we do not send email if score is too high, but we do not block registration since we need to allow also real users to register even if they trigger false positives in spam check
            // use the request ip and user agent to also double check if the same ip or user agent was used for other registrations with high spam score, in that case we can increase the spam score of the current registration
        ];

        if ($this->useMinimalForm($race)) {
            return Arr::except($rules, [
                'driver_licence_type',
                'driver_licence_renewed_at',
                'driver_medical_certificate_expiration_date',
                'driver_sex',
            ]);
        }

        if (! $this->useCompleteForm($race)) {
            return Arr::except($rules, [
                'driver_licence_renewed_at',
                'driver_sex',
            ]);
        }

        return $rules;
    }

    protected function getCompetitorValidationRules(?Race $race = null): array
    {
        $championship = $race?->championship;
        $acceptedCompetitorLicences = $championship?->licences->accepted_competitor_licences ?? [];

        $competitorLicenceTypeRules = ['nullable', 'required_with:competitor_licence_number', new Enum(CompetitorLicence::class)];
        if (! empty($acceptedCompetitorLicences)) {
            $competitorLicenceTypeRules[] = Rule::in($acceptedCompetitorLicences);
        }

        $rules = [
            'competitor_licence_number' => ['sometimes', 'nullable', 'string', 'max:250', 'min:3', new LicenseNumberValidationRule],
            'competitor_licence_type' => $competitorLicenceTypeRules,
            'competitor_first_name' => ['nullable', 'required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_last_name' => ['nullable', 'required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_fiscal_code' => ['nullable', 'string', 'max:250', new FiscalCodeFormatRule(check_competitor: true)],
            'competitor_licence_renewed_at' => ['nullable'],
            'competitor_nationality' => ['nullable', 'required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_email' => ['nullable', 'required_with:competitor_licence_number', 'string', 'email'],
            'competitor_phone' => ['nullable', 'required_with:competitor_licence_number', 'string'],
            'competitor_birth_date' => ['nullable', 'required_with:competitor_licence_number', 'string', new DateFormat],
            'competitor_birth_place' => ['nullable', 'required_with:competitor_licence_number', 'string'],
            'competitor_residence_address' => ['nullable', 'required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_residence_city' => ['nullable', 'required_with:competitor_licence_number', 'string',  'max:250'],
            'competitor_residence_province' => ['nullable', 'string',  'max:250'],
            'competitor_residence_postal_code' => ['nullable', 'required_with:competitor_licence_number', 'string', 'max:250'],
        ];

        if ($this->useMinimalForm($race)) {
            return Arr::except($rules, [
                'competitor_licence_type',
                'competitor_licence_renewed_at',
            ]);
        }

        if (! $this->useCompleteForm($race)) {
            return Arr::except($rules, [
                'competitor_licence_renewed_at',
            ]);
        }

        return $rules;
    }

    protected function getMechanicValidationRules(?Race $race = null): array
    {
        if (! $this->useCompleteForm($race)) {
            return [];
        }

        return [
            'mechanic_licence_number' => ['nullable', 'string', 'max:250', 'min:3', new LicenseNumberValidationRule],
            'mechanic_name' => ['nullable', 'required_with:mechanic_licence_number', 'string', 'max:250'],
        ];
    }

    protected function getVehicleValidationRules(?Race $race = null): array
    {
        if (! $this->useCompleteForm($race)) {
            return [];
        }

        return [
            'vehicle_chassis_manufacturer' => ['required', 'string', 'max:250'],
            'vehicle_chassis_model' => ['nullable', 'string', 'max:250'],
            'vehicle_chassis_homologation' => ['nullable', 'string', 'max:250'],
            'vehicle_chassis_number' => ['nullable', 'string', 'max:250'],
            'vehicle_engine_manufacturer' => ['required', 'string',  'max:250'],
            'vehicle_engine_model' => ['required', 'string',  'max:250'],
            'vehicle_engine_homologation' => ['nullable', 'string', 'max:250'],
            'vehicle_engine_number' => ['nullable', 'string', 'max:250'],
            'vehicle_oil_manufacturer' => ['required', 'string', 'max:250'],
            'vehicle_oil_type' => ['nullable', 'string',  'max:250'],
            'vehicle_oil_percentage' => ['required', 'string', 'max:250'],
        ];
    }

    protected function useMinimalForm(?Race $race = null): bool
    {
        return RegistrationForm::resolve($race) === RegistrationForm::Minimal;
    }

    protected function useCompleteForm(?Race $race = null): bool
    {
        return RegistrationForm::resolve($race) === RegistrationForm::Complete;
    }

    /**
     * Check if the driver is using the assigned bib or choose a unique number within the championship
     */
    protected function ensureDriverUsesUniqueOrAssignedBib(array $input, Race $race, ?User $user = null): array
    {
        $licenceHash = $input['driver_licence_number'];

        $validator = Validator::make($input, [
            'bib' => [
                Rule::unique('participants', 'bib')->where(fn ($query) => $query->where('race_id', $race->getKey())),

                Rule::unique('participants', 'bib')
                    ->where(fn ($query) => $query
                        ->where('championship_id', $race->championship_id)
                        ->where('driver_licence', '!=', $input['driver_licence_number'])),
            ],
            'driver_licence_number' => [
                Rule::unique('participants', 'driver_licence')
                    ->where(fn ($query) => $query->where('race_id', $race->getKey())),
            ],
        ])
            ->after(function ($validator) use ($race, $input) {

                $validated = $validator->validated();

                $bibs = collect(Participant::query()
                    ->where('championship_id', $race->championship_id)
                    ->licenceHash($validated['driver_licence_number'])
                    ->select(['bib'])
                    ->get(['bib'])->pluck('bib'))->unique()->filter();

                // check if participant by licence has equal bib
                if ($bibs->isNotEmpty() && ! $bibs->contains($validated['bib'])) {
                    $validator->errors()->add(
                        'bib', 'The entered bib does not reflect what has been used so far in the championship by the same driver.'
                    );
                }

                // check if bib was reserved to other driver

                $this->ensureBibNotReservedByOtherDriver($validator, $race->championship_id, [
                    'driver_first_name' => $input['driver_first_name'],
                    'driver_last_name' => $input['driver_last_name'],
                    'bib' => $validated['bib'],
                    'driver_licence_number' => $validated['driver_licence_number'],
                ]);

            });

        return $validator->validate();
    }

    protected function ensureBibNotReservedByOtherDriver(ValidatorContract $validator, $championship_id, $input)
    {
        try {

            $reservedBibWithSameLicence = BibReservation::query()
                ->notExpired()
                ->inChamphionship($championship_id)
                ->licenceHash($input['driver_licence_number'])
                ->first()?->bib;

            if ($reservedBibWithSameLicence && (int) $reservedBibWithSameLicence !== (int) ($input['bib'])) {

                logs()->error('Participant validation: ensure bib not reserved. Failure 1', [
                    'championship_id' => $championship_id,
                    'input_licence' => $input['driver_licence_number'],
                    'input_bib' => $input['bib'],
                    'reserved_bib' => $reservedBibWithSameLicence,
                ]);

                $validator->errors()->add(
                    'bib', __('The entered bib (:entered) does not reflect what has been reserved (:reserved) to the driven with the given licence.', [
                        'entered' => $input['bib'],
                        'reserved' => $reservedBibWithSameLicence,
                    ])
                );
            }

            $reservedBib = BibReservation::query()
                ->notExpired()
                ->inChamphionship($championship_id)
                ->raceNumber($input['bib'])
                ->first();

            if ($reservedBib && $reservedBib->isEnforcedUsingLicence() && ! $reservedBib->isReservedToLicenceHash($input['driver_licence_number'])) {
                logs()->error('Participant validation: ensure bib not reserved. Failure 2', [
                    'championship_id' => $championship_id,
                    'input_licence' => $input['driver_licence_number'],
                    'input_bib' => $input['bib'],
                    'reserved_bib' => $reservedBib?->bib,
                    'reserved_licence' => $reservedBib?->driver_licence_hash,
                ]);
                $validator->errors()->add(
                    'bib', __('The entered bib is already reserved to another driver. Please check your licence number or contact the support reporting error [pvr-2].')
                );
            }

        } catch (Throwable $th) {

            logs()->error('Participant validation: ensure bib not reserved failure: '.$th->getMessage(), [
                'championship_id' => $championship_id,
                'input_licence' => $input['driver_licence_number'],
                'input_bib' => $input['bib'],
                'reserved_bib' => $reservedBib?->bib,
                'reserved_licence' => $reservedBib?->driver_licence_hash,
            ]);

            report($th);

        }
    }
}
