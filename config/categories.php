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
            'tires' => 'Vega Mini',
        ],
        
        '60 MINI GR3' => [
            'name' => '60 Mini Group 3',
            'tires' => 'MG SC',
        ],
        
        '60 MINI MK' => [
            'name' => '60 Mini Kart',
            'description' => 'with engine approval 2010-2014',
            'tires' => 'Vega Mini',
        ],
        
        '60 MINI TERR' => [
            'name' => '60 Mini Territorial',
            'description' => 'For all engine trophies (e.g. X30, Rok, ...)',
            'tires' => 'Vega Mini',
        ],
        
        '125 JUNIOR TERR' => [
            'name' => '125 Junior Territorial',
            'tires' => '',
        ],
        
        '125 JUNIOR OK' => [
            'name' => '125 Junior OK',
            'tires' => '',
        ],
        
        '125 JUNIOR OK-N' => [
            'name' => '125 Junior OK-N',
            'tires' => '',
        ],
        
        '125 SENIOR TERR' => [
            'name' => '125 Senior Territorial',
            'tires' => '',
        ],
        
        '125 SENIOR OK' => [
            'name' => '125 Senior OK',
            'tires' => '',
        ],
        
        '125 SENIOR OK-N' => [
            'name' => '125 Senior OK-N',
            'tires' => '',
        ],
        
        '125 MASTER TERR' => [
            'name' => '125 Master Territorial',
            'tires' => '',
        ],
        
        '125 MASTER OK-N' => [
            'name' => '125 Master OK-N',
            'tires' => '',
        ],
        
        '125 KZ 2' => [
            'name' => '125 KZ 2',
            'tires' => '',
        ],
        
        '125 KZ 2 UNDER' => [
            'name' => '125 KZ 2 under',
            'tires' => '',
        ],
        
        '125 KZ 2 OVER' => [
            'name' => '125 KZ 2 over',
            'tires' => '',
        ],
        
        '125 KZ N OVER 25' => [
            'name' => '125 KZ N over 25',
            'tires' => '',
        ],
        
        '125 KZ N OVER 30' => [
            'name' => '125 KZ N over 30',
            'tires' => '',
        ],
        
        '125 KZ N OVER 50' => [
            'name' => '125 KZ N over 50',
            'tires' => '',
        ],
        
        '125 KZ N ROOKI' => [
            'name' => '125 KZ N rookie',
            'tires' => '',
        ],
        
        '125 KZ OLI' => [
            'name' => '125 KZ OLI',
            'tires' => '',
        ],
        
        '125 KZ TOP DRIVER' => [
            'name' => '125 KZ TOP DRIVER',
            'tires' => '',
        ],
        
        '270 FOURSTROKE' => [
            'name' => '270 Fourstroke (GR1 - GR2)',
            'tires' => 'VEGA SL4',
        ],

        '100 LEGEND' => [
            'name' => '100 Legend',
            'tires' => 'VEGA SL4',
        ],
        '125 LEGEND AIR' => [
            'name' => '125 Legend Aria',
            'tires' => 'VEGA SL4',
        ],
        '125 LEGEND WATER' => [
            'name' => '125 Legend Acqua',
            'tires' => 'VEGA SL4',
        ],
    ]

    

];
