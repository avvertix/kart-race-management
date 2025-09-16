<?php

declare(strict_types=1);

use Carbon\Carbon;

return [

    /*
    |--------------------------------------------------------------------------
    | Races Configuration
    |--------------------------------------------------------------------------
    |
    */

    'start_time' => env('RACE_START_TIME', '09:00:00'),

    'end_time' => env('RACE_END_TIME', '18:00:00'),

    'timezone' => env('RACE_TIMEZONE', 'Europe/Rome'),

    'registration' => [
        'opens' => env('RACE_REGISTRATION_OPENS_HOURS', 7 * Carbon::HOURS_PER_DAY),
        'closes' => env('RACE_REGISTRATION_CLOSES_HOURS', 1),

        /*
         * Acceptable values: complete, minimal
         */
        'form' => env('RACE_REGISTRATION_FORM', 'complete'),
    ],

    'organizer' => [
        'name' => env('RACE_ORGANIZER_NAME'),
        'email' => env('RACE_ORGANIZER_EMAIL'),
        'url' => env('RACE_ORGANIZER_URL'),
        'address' => env('RACE_ORGANIZER_ADDRESS'),
        'vat' => env('RACE_ORGANIZER_VAT'),
        'bank_holder' => env('RACE_ORGANIZER_BANK_HOLDER', env('RACE_ORGANIZER_NAME')),
        'bank_account' => env('RACE_ORGANIZER_BANK_ACCOUNT'),
        'bank' => env('RACE_ORGANIZER_BANK'),
    ],

    'licence' => [
        'provider' => env('RACE_LICENCE_PROVIDER', 'ACI Sport'),
        'country' => env('RACE_LICENCE_COUNTRY', 'it'),
    ],

    'price' => env('RACE_PRICE', '15000'), // Expressed in decimal notation
    'price_currency' => env('RACE_PRICE_CURRENCY', 'EUR'),
    'bonus_amount' => env('RACE_BONUS_AMOUNT', env('RACE_PRICE', '15000')),
    'bonus_use_one_at_time' => env('RACE_BONUS_USE_ONE_AT_TIME', true),

];
