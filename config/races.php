<?php

use Carbon\Carbon;

return [

    /*
    |--------------------------------------------------------------------------
    | Races Configuration
    |--------------------------------------------------------------------------
    |
    */

    'start_time' => '09:00:00',
    
    'end_time' => '18:00:00',

    'registration' => [
        'opens' => env('RACE_REGISTRATION_OPENS_HOURS', 7 * Carbon::HOURS_PER_DAY),
        'closes' => env('RACE_REGISTRATION_CLOSES_HOURS', 1),
    ],

    'organizer' => [
        'name' => env('RACE_ORGANIZER_NAME'),
        'email' => env('RACE_ORGANIZER_EMAIL'),
        'address' => env('RACE_ORGANIZER_ADDRESS'),
        'bank_account' => env('RACE_ORGANIZER_BANK_ACCOUNT'),
    ],

    'licence' => [
        'provider' => env('RACE_LICENCE_PROVIDER', 'ACI Sport'),
        'country' => env('RACE_LICENCE_COUNTRY', 'Italia')
    ],

];
