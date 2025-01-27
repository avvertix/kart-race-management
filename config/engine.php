<?php

declare(strict_types=1);

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
        'tm kart' => 'TM',
        'tm r1' => 'TM',
        'modena engine' => 'MODENA',
        'modena engines' => 'MODENA',
        'sgm - severi racing kart srl' => 'SGM',
    ],

];
