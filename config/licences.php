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
            'naz-f' => [
                'name' => 'NAZ F',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'int-f' => [
                'name' => 'INT F',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_INTERNATIONAL,
            ],
            'naz-g' => [
                'name' => 'NAZ G',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'int-g' => [
                'name' => 'INT G',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_INTERNATIONAL,
            ],
            'k-mini' => [
                'name' => 'K MINI',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'easyrace' => [
                'name' => 'Easy Race',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::EASYRACE,
            ],
            'mechanic' => [
                'name' => 'Mechanic',
                'role' => LicenceRole::Mechanic,
            ],
        ],

        'asi' => [
            'ma' => [
                'name' => 'MA',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'lkm' => [
                'name' => 'LKM',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'k' => [
                'name' => 'K',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'c1' => [
                'name' => 'C1',
                'role' => LicenceRole::Driver,
                'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            ],
            'mechanic' => [
                'name' => 'Mechanic',
                'role' => LicenceRole::Mechanic,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Licence Types handled by this instance
    |--------------------------------------------------------------------------
    |
    | The Licence Types this deployment works with, as "provider.code", e.g.
    | LICENCE_TYPES=asi.ma,asi.mechanic. Licences and requests of other
    | types are not offered, enrolled, renewed or used in race registration;
    | they keep their name. Empty: every type listed above.
    |
    */

    'enabled_types' => array_values(array_filter(array_map(trim(...), explode(',', (string) env('LICENCE_TYPES', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Renewal price
    |--------------------------------------------------------------------------
    |
    | What a renewal outside enrollment costs, in euro, shown to requesters
    | before they submit: a price (50) or a range (50-80). A price also
    | pre-fills the amount the organizer sets during review.
    |
    */

    'renewal_price' => env('LICENCE_RENEWAL_PRICE'),

    /*
    |--------------------------------------------------------------------------
    | Forms published by the providers
    |--------------------------------------------------------------------------
    |
    | Forms to sign that a Licence Provider publishes in a format the platform
    | cannot generate, keyed by provider and then by LicenceDocument value,
    | e.g. 'aci' => ['privacy' => 'https://…/privacy.pdf']. Applicants
    | download them from the provider's website and fill them in by hand;
    | forms not listed here are generated pre-filled by the platform.
    |
    */

    'forms' => [
        'aci' => [
            'privacy' => 'https://www.acisport.it/public_federazione/2019/pdf/Modulistica/informativa_privacy_licenziati_.pdf',
            'parental_authorisation' => 'https://www.acisport.it/public_federazione/2017/pdf/Modulistica/autorizzazione_esercenti_potesta_genitoriale_kart.doc',
            'delegation' => 'https://www.kartsportcircuit.info/wp-content/uploads/2021/12/DELEGA-3-2-1-1-2-1.docx',
        ],
        'asi' => [
            'privacy' => 'https://www.motosportcircuit.com/wp-content/uploads/2026/11/ASI-GDPR.pdf',
        ],
    ],

];
