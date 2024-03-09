<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Sex;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\CompetitorLicence;
use App\Validation\ParticipantValidationRules;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class UpdateParticipantRegistration
{

    use ParticipantValidationRules;

    /**
     * Validate and create a new race participant.
     *
     * @param  array  $input
     * @return \App\Models\Participant
     */
    public function __invoke(Race $race, Participant $participant, array $input, ?User $user = null): Participant
    {
        $validatedInput = Validator::make($input, [
            ...$this->getBibValidationRules(),
            ...$this->getCategoryValidationRules((int)$race->championship_id),
            ...$this->getDriverValidationRules(),
            ...$this->getCompetitorValidationRules(),
            ...$this->getMechanicValidationRules(),
            ...$this->getVehicleValidationRules(),
        ])->validate();

        $category = Category::whereUlid($validatedInput['category'])->firstOrFail();

        $originalDriverEmail = $participant->driver['email'] ?? '';

        try {

            $licenceHash = hash('sha512', $validatedInput['driver_licence_number']);

            $updatedParticipant = Cache::lock("participant:{$validatedInput['bib']}", 10)->block(5, function() use ($validatedInput, $participant, $category, $licenceHash){

                $validatedBib = Validator::make($validatedInput, [
                    'bib' => [
                        Rule::unique('participants', 'bib')->ignore($participant)->where(fn ($query) => $query->where('race_id', $participant->race->getKey())),
                        Rule::unique('participants', 'bib')
                            ->where(fn ($query) => $query
                                ->where('championship_id', $participant->race->championship_id)
                                ->where('driver_licence', '!=', $licenceHash)),
                    ],
                ])
                ->after(function($validator) use ($participant, $validatedInput, $licenceHash) {
                    $validated = $validator->validated();

                    $this->ensureBibNotReservedByOtherDriver($validator, $participant->race->championship_id, [
                        'driver_first_name' => $validatedInput['driver_first_name'],
                        'driver_last_name' => $validatedInput['driver_last_name'],
                        'bib' => $validatedInput['bib'],
                        'driver_licence_number' => $licenceHash,
                    ]);

                })
                ->validate();

                

                $participant->update([
                    'bib' => $validatedInput['bib'],
                    'category' => $validatedInput['category'],
                    'category_id' => $category->getKey(),
                    'first_name' => $validatedInput['driver_first_name'],
                    'last_name' => $validatedInput['driver_last_name'],
                    'driver_licence' => hash('sha512', $validatedInput['driver_licence_number']),
                    'competitor_licence' => isset($validatedInput['competitor_licence_number']) ? hash('sha512', $validatedInput['competitor_licence_number']) : null,
                    'licence_type' => $validatedInput['driver_licence_type'] ?? DriverLicence::LOCAL_NATIONAL->value,
                    'driver' => [
                        'first_name' => $validatedInput['driver_first_name'],
                        'last_name' => $validatedInput['driver_last_name'],
                        'licence_type' => $validatedInput['driver_licence_type'] ?? DriverLicence::LOCAL_NATIONAL->value,
                        'licence_number' => $validatedInput['driver_licence_number'],
                        'fiscal_code' => $validatedInput['driver_fiscal_code'],
                        'licence_renewed_at' => $validatedInput['driver_licence_renewed_at'] ?? null,
                        'nationality' => $validatedInput['driver_nationality'],
                        'email' => $validatedInput['driver_email'],
                        'phone' => $validatedInput['driver_phone'],
                        'birth_date' => $validatedInput['driver_birth_date'],
                        'birth_place' => $validatedInput['driver_birth_place'],
                        'medical_certificate_expiration_date' => $validatedInput['driver_medical_certificate_expiration_date'] ?? null,
                        'residence_address' =>  $this->processAddressInput($validatedInput, 'driver_residence'),
                        'sex' => $validatedInput['driver_sex'] ?? Sex::UNSPECIFIED,
                    ],
                    'competitor' => isset($validatedInput['competitor_licence_number']) ? [
                        'first_name' => $validatedInput['competitor_first_name'],
                        'last_name' => $validatedInput['competitor_last_name'],
                        'licence_type' => $validatedInput['competitor_licence_type'] ?? CompetitorLicence::LOCAL->value,
                        'licence_number' => $validatedInput['competitor_licence_number'],
                        'fiscal_code' => $validatedInput['competitor_fiscal_code'],
                        'licence_renewed_at' => $validatedInput['competitor_licence_renewed_at'] ?? null,
                        'nationality' => $validatedInput['competitor_nationality'],
                        'email' => $validatedInput['competitor_email'],
                        'phone' => $validatedInput['competitor_phone'],
                        'birth_date' => $validatedInput['competitor_birth_date'],
                        'birth_place' => $validatedInput['competitor_birth_place'],
                        'residence_address' => $this->processAddressInput($validatedInput, 'competitor_residence'),
                    ] : null,
                    'mechanic' => isset($validatedInput['mechanic_name']) && isset($validatedInput['mechanic_licence_number']) ? [
                        'name' => $validatedInput['mechanic_name'],
                        'licence_number' => $validatedInput['mechanic_licence_number'],
                    ] : null,
                    'vehicles' => $this->processVehicle($validatedInput),
                ]);
                
                return $participant;
            });

            if($originalDriverEmail !== $updatedParticipant->driver['email']){
                $updatedParticipant->sendConfirmParticipantNotification();
            }

            return $updatedParticipant;
        
        } catch (LockTimeoutException $th) {
            throw ValidationException::withMessages([
                'bib' => __('The race number has already been taken.'),
            ]);
        }
    }
    
}
