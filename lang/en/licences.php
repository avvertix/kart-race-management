<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Licence Language Lines
    |--------------------------------------------------------------------------
    |
    |
    */

    'driver' => [
        'LOCAL_NATIONAL' => 'National',
        'LOCAL_INTERNATIONAL' => 'International',
        'FOREIGN' => 'Foreign',
        'EASYRACE' => 'Easyrace',
        'DAILY' => 'Daily',
    ],

    'competitor' => [
        'LOCAL' => 'Nazional',
        'FOREIGN' => 'Foreign',
    ],

    'provider' => [
        'aci' => 'ACI Sport',
        'asi' => 'ASI',
    ],

    'role' => [
        'driver' => 'Driver',
        'competitor' => 'Competitor',
        'mechanic' => 'Mechanic',
    ],

    'level' => [
        'national' => 'National',
        'international' => 'International',
    ],

    'document_kind' => [
        'upload' => 'Upload',
        'signed_form' => 'Form to sign',
    ],

    'document_scope' => [
        'holder' => 'Holder',
        'each_parent' => 'Each parent or guardian',
        'once' => 'Once per request',
    ],

    'form_template' => [
        'delegation' => 'Delegation',
        'privacy' => 'Privacy',
        'parental_authorisation' => 'Parental authorisation',
        'self_declaration' => 'Self-declaration',
    ],

];
