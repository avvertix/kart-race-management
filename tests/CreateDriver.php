<?php

namespace Tests;

use App\Models\DriverLicence;
use App\Models\Sex;

trait CreateDriver
{
    /**
     * Generate a driver request parameters
     * 
     * @return array
     */
    protected function generateValidDriver()
    {
        return [
            'driver_first_name' => 'John',
            'driver_last_name' => 'Racer',
            'driver_licence_number' => 'D0001',
            'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
            'driver_licence_renewed_at' => null,
            'driver_nationality' => 'Italy',
            'driver_email' => 'john@racer.local',
            'driver_phone' => '555555555',
            'driver_birth_date' => '1999-11-11',
            'driver_birth_place' => 'Milan',
            'driver_medical_certificate_expiration_date' => today()->addYear()->toDateString(),
            'driver_residence_address' => 'via dei Platani, 40',
            'driver_residence_city' => 'Milan',
            'driver_residence_province' => 'Milan',
            'driver_residence_postal_code' => '20146',
            'driver_sex' => Sex::MALE->value,
            'driver_fiscal_code' => 'DRV-FC',
        ];
    }

}
