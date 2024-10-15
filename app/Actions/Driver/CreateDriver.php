<?php

namespace App\Actions\Driver;

use App\Data\AddressData;
use App\Data\BirthData;
use App\Data\LicenceData;
use App\Events\ParticipantRegistered;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\Sex;
use App\Models\DriverLicence;
use App\Models\CompetitorLicence;
use App\Models\Driver;
use App\Validation\ParticipantValidationRules;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CreateDriver
{

    use ParticipantValidationRules;

    /**
     * Creates a new driver.
     *
     * @param \App\Models\Championship $championship
     * @param  array  $input The raw data with the driver details
     * @param \App\Models\User|null $user The user that is performing the operation
     * @return \App\Models\Driver
     */
    public function __invoke(Championship $championship, array $input, ?User $user = null): Driver
    {
        // TODO: uniqueness validation

        $validatedInput = Validator::make($input, [
            ...$this->getBibValidationRules(),
            ...$this->getDriverValidationRules(),
        ])->validate();

        $licence = LicenceData::from([
            'number' => $validatedInput['driver_licence_number'],
            'type' => $validatedInput['driver_licence_type'] ?? null,
        ]);
    
        $driver = Cache::lock($this->getLockKey($licence->hash()), 10)
            ->block(5, function() use($championship, $validatedInput, $user, $licence){

                $birthData = BirthData::from([
                    'date' => Carbon::parse($validatedInput['driver_birth_date']),
                    'place' => $validatedInput['driver_birth_place'] ?? null,
                ]);
                
                $addressData = AddressData::from([
                    'address' => $validatedInput['driver_residence_address'] ?? null,
                    'city' => $validatedInput['driver_residence_city'] ?? null,
                    'province' => $validatedInput['driver_residence_province'] ?? null,
                    'postal_code' => $validatedInput['driver_residence_postal_code'] ?? null,
                ]);

                $driver = $championship->drivers()->create([
                    'code' => $licence->shortHash(),
                    'bib' => $validatedInput['bib'],
                    'first_name' => $validatedInput['driver_first_name'],
                    'last_name' => $validatedInput['driver_last_name'],
                    'user_id' => $user?->getKey(),
                    'licence_number' => $licence->number,
                    'licence_hash' => $licence->hash(),
                    'licence_type' => $licence->type,
                    'fiscal_code' => $validatedInput['driver_fiscal_code'],
                    'email' => $validatedInput['driver_email'],
                    'phone' => $validatedInput['driver_phone'],
                    'birth' => $birthData,
                    'birth_date_hash' => $birthData->hash(),
                    'address' => $addressData,
                    'medical_certificate_expiration_date' => $validatedInput['driver_medical_certificate_expiration_date'] ?? null,
                ]);

                return $driver;
            });
        
        return $driver;
    }

    protected function getLockKey($seed)
    {
        return "driver:{$seed}";
    }
    
}
