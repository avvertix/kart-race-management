<?php

declare(strict_types=1);

use App\Enums\LicenceRole;
use App\Models\DriverLicence;

return [

    /*
    |--------------------------------------------------------------------------
    | Licence Types
    |--------------------------------------------------------------------------
    |
    | The licences each Licence Provider issues, keyed by provider and then by
    | code. The code is stored with Licences and Licence Requests: never
    | change or reuse it, set "active" to false to stop offering a type.
    |
    | - name: as printed on the licence, not translated
    | - role: LicenceRole::Driver or LicenceRole::Mechanic
    | - race_licence_category: the DriverLicence it counts as in race
    |   registration, drivers only
    | - active: optional, defaults to true
    |
    | See docs/how-to/maintainer/add-licence-providers-and-configuration.md
    |
    */

    'types' => [

        'aci' => [
            'naz-e' => [
                'name' => 'NAZ E',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'int-e' => [
                'name' => 'INT E',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_INTERNATIONAL,
            ],
            'daily' => [
                'name' => 'Daily',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::DAILY,
            ],
            'easyrace' => [
                'name' => 'EasyRace',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::EASYRACE,
            ],
            'mechanic' => [
                'name' => 'Mechanic',
                'role' => LicenceRole::Mechanic,
            ],
        ],

        'asi' => [
            'driver' => [
                'name' => 'Driver',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'mechanic' => [
                'name' => 'Mechanic',
                'role' => LicenceRole::Mechanic,
            ],
        ],

    ],

];
