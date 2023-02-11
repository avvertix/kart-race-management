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
        'url' => env('RACE_ORGANIZER_URL'),
        'address' => env('RACE_ORGANIZER_ADDRESS'),
        'bank_account' => env('RACE_ORGANIZER_BANK_ACCOUNT'),
        'bank' => env('RACE_ORGANIZER_BANK'),
    ],

    'licence' => [
        'provider' => env('RACE_LICENCE_PROVIDER', 'ACI Sport'),
        'country' => env('RACE_LICENCE_COUNTRY', 'it')
    ],

    'price' => env('RACE_PRICE', '15000'), // Expressed in decimal notation
    'price_currency' => env('RACE_PRICE_CURRENCY', 'EUR'),
    'bonus_amount' => env('RACE_BONUS_AMOUNT', env('RACE_PRICE', '15000'),),


    
    'tires' => [
        'VEGA_SL4' => [
            'name' => 'VEGA SL4',
            'price' => '16500',
        ],
        'VEGA_MINI' => [
            'name' => 'VEGA MINI',
            'price' => '14000',
        ],
        'VEGA_XH3' => [
            'name' => 'VEGA XH3',
            'price' => '21000',
        ],
        'MG_SC' => [
            'name' => 'MG SC',
            'price' => '15500',
        ],
        'MG_SM' => [
            'name' => 'MG SM',
            'price' => '20500',
        ],
    ],

];
