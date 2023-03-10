<?php

use Carbon\Carbon;

return [

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    */

    'file' => 'categories.json',

    'disk' => env('FILESYSTEM_DISK', 'local'),

    'default' => [

        '60 MINI SM' => [
            'name' => '60 Mini SM',
            'description' => 'with engine approval 2015-2022 (green control unit)',
            'tires' => 'VEGA_MINI',
        ],
        
        '60 MINI GR3' => [
            'name' => '60 Mini Group 3',
            'tires' => 'MG_SC',
        ],
        
        '60 MINI GR3 UNDER 10' => [
            'name' => '60 Mini Group 3 under 10',
            'tires' => 'MG_SC',
        ],
        
        '60 MINI MK' => [
            'name' => '60 Mini Kart',
            'description' => 'with engine approval 2010-2014',
            'tires' => 'VEGA_MINI',
        ],
        
        '60 MINI TDM ROK' => [
            'name' => '60 Mini Rok (TDM)',
            'tires' => 'VEGA_MINI',
        ],
        
        '60 MINI TDM EASY' => [
            'name' => '60 Easy (TDM)',
            'tires' => 'VEGA_MINI',
        ],
        
        '60 MINI TDM FR ROTAX' => [
            'name' => '60 Mini FR Rotax (TDM)',
            'tires' => 'VEGA_MINI',
        ],
        
        '60 MINI TDM X30' => [
            'name' => '60 Mini X30 (TDM)',
            'tires' => 'VEGA_MINI',
        ],

        '60 MINI TERR' => [
            'name' => '60 Mini Territorial',
            'description' => 'For all engine trophies (e.g. X30, Rok, ...)',
            'tires' => 'VEGA_MINI',
        ],
        
        '125 JUNIOR TERR' => [
            'name' => '125 Junior Territorial',
            'tires' => 'VEGA_XH3',
        ],
        
        '125 JUNIOR OK' => [
            'name' => '125 Junior OK',
            'tires' => 'VEGA_XH3',
        ],
        
        '125 JUNIOR OK-N' => [
            'name' => '125 Junior OK-N',
            'tires' => 'VEGA_XH3',
        ],
        
        '125 JUNIOR TDM ROK' => [
            'name' => '125 Junior Rok (TDM)',
            'tires' => 'VEGA_XH3',
        ],
        '100 JUNIOR TDM EASY' => [
            'name' => '100 Easy (TDM)',
            'tires' => 'VEGA_XH3',
        ],
        '125 JUNIOR TDM X30' => [
            'name' => '125 Junior X30 (TDM)',
            'tires' => 'VEGA_XH3',
        ],
        
        '125 SENIOR TERR' => [
            'name' => '125 Senior Territorial',
            'tires' => 'MG_SM',
        ],
        
        '125 SENIOR OK' => [
            'name' => '125 Senior OK',
            'tires' => 'MG_SM',
        ],
        
        '125 SENIOR OK-N' => [
            'name' => '125 Senior OK-N',
            'tires' => 'VEGA_XH3',
        ],

        '125 SENIOR TDM ROK' => [
            'name' => '125 Senior Rok (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM SUPEROK' => [
            'name' => '125 Senior Superok (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM ROTAX MAX' => [
            'name' => '125 Senior Rotax Max (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM BMB' => [
            'name' => '125 Senior BMB (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM X30' => [
            'name' => '125 Senior X30 (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM SHIFTER ROK' => [
            'name' => '125 Senior Shifter Rok (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM KGP SHIFTER' => [
            'name' => '125 Senior KGP Shifter (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM X30 SUPER SHIFTER' => [
            'name' => '125 Senior X30 Super Shifter (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM KGP DIRECT DRIVE' => [
            'name' => '125 Senior KGP Direct Drive (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM ROTAX DD2' => [
            'name' => '125 Senior Rotax DD2 (TDM)',
            'tires' => 'MG_SM',
        ],

        '125 SENIOR TDM X30 SUPER' => [
            'name' => '125 Senior X30 Super (TDM)',
            'tires' => 'MG_SM',
        ],
        
        '125 MASTER TERR' => [
            'name' => '125 Master Territorial',
            'tires' => 'MG_SM',
        ],
        
        '125 MASTER OK-N' => [
            'name' => '125 Master OK-N',
            'tires' => 'MG_SM',
        ],
        
        '125 KZ TERR' => [
            'name' => '125 KZ Territorial',
            'tires' => 'VEGA_SL4',
        ],
        
        '125 KZ 2' => [
            'name' => '125 KZ 2',
            'tires' => 'MG_SM',
        ],
        
        '125 KZ 2 UNDER' => [
            'name' => '125 KZ 2 under',
            'tires' => 'MG_SM',
        ],
        
        '125 KZ 2 OVER' => [
            'name' => '125 KZ 2 over',
            'tires' => 'MG_SM',
        ],
        
        '125 KZ N OVER 25' => [
            'name' => '125 KZ N over 25',
            'tires' => 'VEGA_SL4',
        ],
        
        '125 KZ N OVER 30' => [
            'name' => '125 KZ N over 30',
            'tires' => 'VEGA_SL4',
        ],
        
        '125 KZ N OVER 50' => [
            'name' => '125 KZ N over 50',
            'tires' => 'VEGA_SL4',
        ],
        
        '125 KZ N ROOKIE' => [
            'name' => '125 KZ N rookie',
            'tires' => 'VEGA_SL4',
        ],
        
        '270 FOURSTROKE' => [
            'name' => '270 Fourstroke (GR1 - GR2)',
            'tires' => 'VEGA_SL4',
        ],

        '100 LEGEND' => [
            'name' => '100 Legend',
            'tires' => 'VEGA_SL4',
        ],
        '125 LEGEND AIR' => [
            'name' => '125 Legend Aria',
            'tires' => 'VEGA_SL4',
        ],
        '125 LEGEND WATER' => [
            'name' => '125 Legend Acqua',
            'tires' => 'VEGA_SL4',
        ],
    ]

    

];
