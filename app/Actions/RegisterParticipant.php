<?php

namespace App\Actions;

use App\Models\BibReservation;
use App\Models\Category;
use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Models\Sex;
use App\Rules\ExistsCategory;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\CompetitorLicence;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterParticipant
{

    /**
     * Validate and create a new race participant.
     *
     * @param  array  $input
     * @return \App\Models\Participant
     */
    public function __invoke(Race $race, array $input, ?User $user = null)
    {
        $validatedParticipantInput = Validator::make($input, [
            'bib' => ['required', 'integer', 'min:1',],
            'category' => [
                'required',
                'string',
                'ulid',
                Rule::exists((new Category())->getTable(), 'ulid')->where(function ($query) use ($race) {
                    return $query->where('championship_id', $race->championship_id)->where('enabled', true);
                }),],

            ...$this->getDriverValidationRules(),
            ...$this->getCompetitorValidationRules(),
            ...$this->getMechanicValidationRules(),
            ...$this->getVehicleValidationRules(),
            
            'consent_privacy' => ['sometimes', 'required', 'accepted'],
        ])->validate();

        $category = Category::whereUlid($validatedParticipantInput['category'])->firstOrFail();

        try {

            $race->loadCount('participants');
        
            $participant = Cache::lock($this->getLockKey($race, $validatedParticipantInput['bib']), 10)->block(5, function() use($race, $validatedParticipantInput, $user, $category){

                $input = array_merge($validatedParticipantInput, ['driver_licence_number' => hash('sha512', $validatedParticipantInput['driver_licence_number'])]);

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
                    ]
                ])
                ->after(function($validator) use ($race, $category, $validatedParticipantInput) {

                    $validated = $validator->validated();

                    $bibs = collect(Participant::query()
                        ->where('championship_id', $race->championship_id)
                        ->licenceHash($validated['driver_licence_number'])
                        ->select(["bib"])
                        ->get(["bib"])->pluck("bib"))->unique()->filter();

                    // check if participant by licence has equal bib
                    if($bibs->isNotEmpty() && !$bibs->contains($validated['bib'])){
                        $validator->errors()->add(
                            'bib', 'The entered bib does not reflect what has been used so far in the championship by the same driver.'
                        );
                    }

                    // check if bib was reserved to other driver

                    $reservedBibWithSameLicence = BibReservation::query()
                        ->notExpired()
                        ->inChamphionship($race->championship_id)
                        ->licenceHash($validated['driver_licence_number'])
                        ->first()?->bib;

                    if($reservedBibWithSameLicence && $reservedBibWithSameLicence != $validated['bib']){
                        $validator->errors()->add(
                            'bib', 'The entered bib does not reflect what has been reserved to the driven with the given licence.'
                        );
                    }
                    
                    $reservedBib = BibReservation::query()
                        ->notExpired()
                        ->inChamphionship($race->championship_id)
                        ->raceNumber($validated['bib'])
                        ->first();

                    if($reservedBib && ! $reservedBib->isEnforcedUsingLicence() && ! $reservedBib->isReservedToLicenceHash($validated['driver_licence_number'])){
                        $validator->errors()->add(
                            'bib', 'The entered bib is already reserved to another driver. Please check your licence number or contact the support.'
                        );
                    }

                    // This covers an edge case when the organized doesn't know the licence number
                    // when adding a reservation. The registration is denied if driver name is
                    // not exactly equal to what is inserted in the reservation
                    if($reservedBib && !$reservedBib->isReservedToDriver([$validatedParticipantInput['driver_first_name'], $validatedParticipantInput['driver_last_name']])){
                        $validator->errors()->add(
                            'bib', 'The entered bib might be reserved to another driver. Please contact the organizer.'
                        );
                    }
                    
                });

                $validator->validate();

                if($race->hasTotalParticipantLimit() && ($race->participants_count + 1) > $race->getTotalParticipantLimit()){
                    throw ValidationException::withMessages([
                        'participants_limit' => __('We reached the maximum allowed participants to this race.')
                    ]);
                }

                $licenceHash = hash('sha512', $validatedParticipantInput['driver_licence_number']);

                $bonus = $race->championship->bonuses()->licenceHash($licenceHash)->first();

                $useBonus = $bonus?->hasRemaining() ?? false;

                $participant = $race->participants()->create([
                    'bib' => $validatedParticipantInput['bib'],
                    'category' => $validatedParticipantInput['category'],
                    'category_id' => $category->getKey(),
                    'first_name' => $validatedParticipantInput['driver_first_name'],
                    'last_name' => $validatedParticipantInput['driver_last_name'],
                    'added_by' => $user?->getKey(),
                    'championship_id' => $race->championship_id,
                    'driver_licence' => $licenceHash,
                    'competitor_licence' => isset($validatedParticipantInput['competitor_licence_number']) ? hash('sha512', $validatedParticipantInput['competitor_licence_number']) : null,
                    'licence_type' => $validatedParticipantInput['driver_licence_type'] ?? DriverLicence::LOCAL_NATIONAL->value,
                    'driver' => [
                        'first_name' => $validatedParticipantInput['driver_first_name'],
                        'last_name' => $validatedParticipantInput['driver_last_name'],
                        'licence_type' => $validatedParticipantInput['driver_licence_type'] ?? DriverLicence::LOCAL_NATIONAL->value,
                        'licence_number' => $validatedParticipantInput['driver_licence_number'],
                        'fiscal_code' => $validatedParticipantInput['driver_fiscal_code'],
                        'licence_renewed_at' => $validatedParticipantInput['driver_licence_renewed_at'] ?? null,
                        'nationality' => $validatedParticipantInput['driver_nationality'],
                        'email' => $validatedParticipantInput['driver_email'],
                        'phone' => $validatedParticipantInput['driver_phone'],
                        'birth_date' => $validatedParticipantInput['driver_birth_date'],
                        'birth_place' => $validatedParticipantInput['driver_birth_place'],
                        'medical_certificate_expiration_date' => $validatedParticipantInput['driver_medical_certificate_expiration_date'] ?? null,
                        'residence_address' =>  $this->processAddressInput($validatedParticipantInput, 'driver_residence'),
                        'sex' => $validatedParticipantInput['driver_sex'] ?? Sex::UNSPECIFIED,
                    ],
                    'competitor' => isset($validatedParticipantInput['competitor_licence_number']) ? [
                        'first_name' => $validatedParticipantInput['competitor_first_name'],
                        'last_name' => $validatedParticipantInput['competitor_last_name'],
                        'licence_type' => $validatedParticipantInput['competitor_licence_type'] ?? CompetitorLicence::LOCAL->value,
                        'licence_number' => $validatedParticipantInput['competitor_licence_number'],
                        'fiscal_code' => $validatedParticipantInput['competitor_fiscal_code'],
                        'licence_renewed_at' => $validatedParticipantInput['competitor_licence_renewed_at'] ?? null,
                        'nationality' => $validatedParticipantInput['competitor_nationality'],
                        'email' => $validatedParticipantInput['competitor_email'],
                        'phone' => $validatedParticipantInput['competitor_phone'],
                        'birth_date' => $validatedParticipantInput['competitor_birth_date'],
                        'birth_place' => $validatedParticipantInput['competitor_birth_place'],
                        'residence_address' => $this->processAddressInput($validatedParticipantInput, 'competitor_residence'),
                    ] : null,
                    'mechanic' => isset($validatedParticipantInput['mechanic_name']) && isset($validatedParticipantInput['mechanic_licence_number']) ? [
                        'name' => $validatedParticipantInput['mechanic_name'],
                        'licence_number' => $validatedParticipantInput['mechanic_licence_number'],
                    ] : null,
                    'vehicles' => $this->processVehicle($validatedParticipantInput),
                    'consents' => [
                        'privacy' => ($validatedParticipantInput['consent_privacy'] ?? false) ? true : false,
                    ],
                    'use_bonus' => $useBonus,
                    'locale' => App::currentLocale(),
                ]);

                if($bonus && $useBonus){
                    $participant->bonuses()->attach($bonus);
                }

                return $participant;
            });

            $participant->sendConfirmParticipantNotification();
            
            return $participant;

        } catch (LockTimeoutException $th) {
            throw ValidationException::withMessages([
                'bib' => __('The race number has already been taken.'),
            ]);
        }
    }

    protected function getLockKey(Race $race, $seed)
    {
        if($race->hasTotalParticipantLimit()){
            return "participant:{$race->uuid}";
        }
        
        return "participant:{$seed}";
    }
    
    protected function processAddressInput($input, $fieldPrefix)
    {
        return [
            'address' => $input[$fieldPrefix.'_address'] ?? null,
            'city' => $input[$fieldPrefix.'_city'] ?? null,
            'province' => $input[$fieldPrefix.'_province'] ?? null,
            'postal_code' => $input[$fieldPrefix.'_postal_code'] ?? null,
        ];
    }

    protected function processVehicle($input)
    {
        if($this->useMinimalForm()){
            return [];
        }

        return [[
            'chassis_manufacturer' => $input['vehicle_chassis_manufacturer'] ?? null,
            'engine_manufacturer' => strtolower($input['vehicle_engine_manufacturer'] ?? ''),
            'engine_model' => strtolower($input['vehicle_engine_model'] ?? ''),
            'oil_manufacturer' => $input['vehicle_oil_manufacturer'] ?? null,
            'oil_type' => $input['vehicle_oil_type'] ?? null,
            'oil_percentage' => $input['vehicle_oil_percentage'] ?? null,
        ]];
    }


    protected function getDriverValidationRules(): array
    {
        $rules = [
            'driver_licence_type' => ['required', new Enum(DriverLicence::class)],
            
            'driver_first_name' => ['required', 'string', 'max:250'],
            'driver_last_name' => ['required', 'string', 'max:250'],
            
            'driver_licence_number' => ['required', 'string', 'max:250'],
            'driver_licence_renewed_at' => ['nullable'],
            'driver_nationality' => ['required', 'string', 'max:250'],
            'driver_email' => ['required', 'string', 'email'],
            'driver_phone' => ['required', 'string', ],
            'driver_birth_date' => ['required', 'string', ],
            'driver_birth_place' => ['required', 'string', ],
            'driver_medical_certificate_expiration_date' => ['required', 'string', ],
            'driver_residence_address' => [ 'required', 'string' ],
            'driver_sex' => [ 'required', new Enum(Sex::class) ],
            'driver_residence_address' => ['required', 'string', 'max:250'],
            'driver_residence_city' => ['required', 'string',  'max:250'],
            'driver_residence_province' => ['nullable', 'string',  'max:250'],
            'driver_residence_postal_code' => ['required', 'string', 'max:250'],
            'driver_fiscal_code' => ['required', 'string', 'max:250'],
        ];

        if($this->useMinimalForm()){
            return Arr::except($rules, [
                'driver_licence_type',
                'driver_licence_renewed_at',
                'driver_medical_certificate_expiration_date',
                'driver_sex',
            ]);
        }

        return $rules;
    }

    protected function getCompetitorValidationRules(): array
    {
        $rules = [
            'competitor_licence_number' => ['sometimes', 'nullable', 'string', 'max:250'],
            'competitor_licence_type' => ['nullable','required_with:competitor_licence_number', new Enum(CompetitorLicence::class)],
            'competitor_first_name' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_last_name' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_fiscal_code' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_licence_renewed_at' => ['nullable'],
            'competitor_nationality' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_email' => ['nullable','required_with:competitor_licence_number', 'string', 'email'],
            'competitor_phone' => ['nullable','required_with:competitor_licence_number', 'string', ],
            'competitor_birth_date' => ['nullable','required_with:competitor_licence_number', 'string', ],
            'competitor_birth_place' => ['nullable','required_with:competitor_licence_number', 'string', ],
            'competitor_residence_address' => [ 'nullable','required_with:competitor_licence_number', 'string' ],
            'competitor_residence_address' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_residence_city' => ['nullable','required_with:competitor_licence_number', 'string',  'max:250'],
            'competitor_residence_province' => ['nullable', 'string',  'max:250'],
            'competitor_residence_postal_code' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
        ];

        if($this->useMinimalForm()){
            return Arr::except($rules, [
                'competitor_licence_type',
                'competitor_licence_renewed_at',
            ]);
        }

        return $rules;
    }

    protected function getMechanicValidationRules(): array
    {
        if($this->useMinimalForm()){
            return [];
        }

        return [
            'mechanic_licence_number' => ['nullable', 'string', 'max:250'],
            'mechanic_name' => ['nullable', 'required_with:mechanic_licence_number', 'string', 'max:250'],
        ];
    }

    protected function getVehicleValidationRules(): array
    {
        if($this->useMinimalForm()){
            return [];
        }

        return [
            'vehicle_chassis_manufacturer' => ['required', 'string', 'max:250'],
            'vehicle_engine_manufacturer' => ['required', 'string',  'max:250'],
            'vehicle_engine_model' => ['required', 'string',  'max:250'],
            'vehicle_oil_manufacturer' => ['required', 'string', 'max:250'],
            'vehicle_oil_type' => ['nullable', 'string',  'max:250'],
            'vehicle_oil_percentage' => ['required', 'string', 'max:250'],
        ];
    }

    protected function useMinimalForm(): bool
    {
        return config('races.registration.form') !== 'complete';
    }
}
