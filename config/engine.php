<?php

use Carbon\Carbon;

return [

    /*
    |--------------------------------------------------------------------------
    | Engines
    |--------------------------------------------------------------------------
    |
    | List of known engine manufactures to suggest and normalizations
    | that can be applied when exporting for MyLaps Orbits
    |
    */

    'manufacturers' => [
        'Iame',
        'Vortex',
        'Rotax',
        'BMB',
        'TM',
    ],

    'normalization' => [
        'tm racing' => 'TM',
        'tiemme' => 'TM',
    ],

];
