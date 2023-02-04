<?php

namespace App\Actions;

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
use Illuminate\Support\Facades\DB;

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

        // Maybe validate driver licence before?

        // TODO: ensure there is a lock on the bib so no one can take it while we validate and insert the records
        // TODO: track consents
        // TODO: track bonus

        $validated = Validator::make($input, [
            'bib' => [
                'required', 'integer', 'min:1', 
                Rule::unique('participants', 'bib')->where(fn ($query) => $query->where('race_id', $race->getKey())),
                
                Rule::unique('participants', 'bib')
                    ->where(fn ($query) => $query
                        ->where('championship_id', $race->championship_id)
                        ->where('driver_licence', '!=', hash('sha512', $input['driver_licence_number'] ?? ''))),
            ],
            'category' => ['required', 'string', new ExistsCategory],
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
            
            'competitor_licence_number' => ['sometimes', 'nullable', 'string', 'max:250'],
            'competitor_licence_type' => ['nullable','required_with:competitor_licence_number', new Enum(CompetitorLicence::class)],
            'competitor_first_name' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_last_name' => ['nullable','required_with:competitor_licence_number', 'string', 'max:250'],
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
            
            'bonus' => ['sometimes', 'required', 'boolean'],
        ])->validate();

        

        return DB::transaction(function() use ($validated, $race, $user){

            $participant = $race->participants()->create([
                'bib' => $validated['bib'],
                'category' => $validated['category'],
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
            ]);

            $participant->sendConfirmParticipantNotification();
            
            return $participant;
        });
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
            'engine_manufacturer' => $input['vehicle_engine_manufacturer'] ?? null,
            'engine_model' => $input['vehicle_engine_model'] ?? null,
            'oil_manufacturer' => $input['vehicle_oil_manufacturer'] ?? null,
            'oil_type' => $input['vehicle_oil_type'] ?? null,
            'oil_percentage' => $input['vehicle_oil_percentage'] ?? null,
        ]];
    }
}
