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
        $validated = Validator::make($input, [
            'bib' => ['required', 'integer', 'min:1',],
            'category' => [
                'required',
                'string',
                'ulid',
                Rule::exists((new Category())->getTable(), 'ulid')->where(function ($query) use ($race) {
                    return $query->where('championship_id', $race->championship_id)->where('enabled', true);
                }),],
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
            
            'mechanic_licence_number' => ['nullable', 'string', 'max:250'],
            'mechanic_name' => ['nullable', 'required_with:mechanic_licence_number', 'string', 'max:250'],

            'vehicle_chassis_manufacturer' => ['required', 'string', 'max:250'],
            'vehicle_engine_manufacturer' => ['required', 'string',  'max:250'],
            'vehicle_engine_model' => ['required', 'string',  'max:250'],
            'vehicle_oil_manufacturer' => ['required', 'string', 'max:250'],
            'vehicle_oil_type' => ['nullable', 'string',  'max:250'],
            'vehicle_oil_percentage' => ['required', 'string', 'max:250'],

            'consent_privacy' => ['sometimes', 'required', 'accepted'],
            
            'bonus' => ['nullable', 'in:true,false'],
        ])->validate();

        $category = Category::whereUlid($validated['category'])->firstOrFail();

        try {

            $race->loadCount('participants');
        
            $participant = Cache::lock($this->getLockKey($race, $validated['bib']), 10)->block(5, function() use($race, $validated, $user, $category){

                $input = array_merge($validated, ['driver_licence_number' => hash('sha512', $validated['driver_licence_number'])]);

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
                ->after(function($validator) use ($race, $category) {

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
                        ->first()?->driver_licence_hash;

                    if($reservedBib && $reservedBib !== $validated['driver_licence_number']){
                        $validator->errors()->add(
                            'bib', 'The entered bib is already reserved to another driver. Please check your licence number or contact the support.'
                        );
                    }
                    
                });

                $validator->validate();

                if($race->hasTotalParticipantLimit() && ($race->participants_count + 1) > $race->getTotalParticipantLimit()){
                    throw ValidationException::withMessages([
                        'participants_limit' => __('We reached the maximum allowed participants to this race.')
                    ]);
                }

                return $race->participants()->create([
                    'bib' => $validated['bib'],
                    'category' => $validated['category'],
                    'category_id' => $category->getKey(),
                    'first_name' => $validated['driver_first_name'],
                    'last_name' => $validated['driver_last_name'],
                    'added_by' => $user?->getKey(),
                    'championship_id' => $race->championship_id,
                    'driver_licence' => hash('sha512', $validated['driver_licence_number']),
                    'competitor_licence' => isset($validated['competitor_licence_number']) ? hash('sha512', $validated['competitor_licence_number']) : null,
                    'licence_type' => $validated['driver_licence_type'],
                    'driver' => [
                        'first_name' => $validated['driver_first_name'],
                        'last_name' => $validated['driver_last_name'],
                        'licence_type' => $validated['driver_licence_type'],
                        'licence_number' => $validated['driver_licence_number'],
                        'fiscal_code' => $validated['driver_fiscal_code'],
                        'licence_renewed_at' => $validated['driver_licence_renewed_at'] ?? null,
                        'nationality' => $validated['driver_nationality'],
                        'email' => $validated['driver_email'],
                        'phone' => $validated['driver_phone'],
                        'birth_date' => $validated['driver_birth_date'],
                        'birth_place' => $validated['driver_birth_place'],
                        'medical_certificate_expiration_date' => $validated['driver_medical_certificate_expiration_date'],
                        'residence_address' =>  $this->processAddressInput($validated, 'driver_residence'),
                        'sex' => $validated['driver_sex'],
                    ],
                    'competitor' => isset($validated['competitor_licence_number']) ? [
                        'first_name' => $validated['competitor_first_name'],
                        'last_name' => $validated['competitor_last_name'],
                        'licence_type' => $validated['competitor_licence_type'],
                        'licence_number' => $validated['competitor_licence_number'],
                        'fiscal_code' => $validated['competitor_fiscal_code'],
                        'licence_renewed_at' => $validated['competitor_licence_renewed_at'] ?? null,
                        'nationality' => $validated['competitor_nationality'],
                        'email' => $validated['competitor_email'],
                        'phone' => $validated['competitor_phone'],
                        'birth_date' => $validated['competitor_birth_date'],
                        'birth_place' => $validated['competitor_birth_place'],
                        'residence_address' => $this->processAddressInput($validated, 'competitor_residence'),
                    ] : null,
                    'mechanic' => isset($validated['mechanic_name']) && isset($validated['mechanic_licence_number']) ? [
                        'name' => $validated['mechanic_name'],
                        'licence_number' => $validated['mechanic_licence_number'],
                    ] : null,
                    'vehicles' => $this->processVehicle($validated),
                    'consents' => [
                        'privacy' => ($validated['consent_privacy'] ?? false) ? true : false,
                    ],
                    'use_bonus' => ($validated['bonus'] ?? false) ? true : false,
                    'locale' => App::currentLocale(),
                ]);
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
        return [[
            'chassis_manufacturer' => $input['vehicle_chassis_manufacturer'] ?? null,
            'engine_manufacturer' => strtolower($input['vehicle_engine_manufacturer'] ?? ''),
            'engine_model' => strtolower($input['vehicle_engine_model'] ?? ''),
            'oil_manufacturer' => $input['vehicle_oil_manufacturer'] ?? null,
            'oil_type' => $input['vehicle_oil_type'] ?? null,
            'oil_percentage' => $input['vehicle_oil_percentage'] ?? null,
        ]];
    }
}
