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
    ],

];
