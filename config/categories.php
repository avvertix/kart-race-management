<?php

declare(strict_types=1);

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
            'name' => 'Minikart',
            'description' => 'with engine approval 2010-2022 (green control unit)',
            'tires' => 'VEGA_MINI',
            'timekeeper_label' => 'MINIKART',
        ],

        '60 MINI GR3' => [
            'name' => 'Mini GR.3',
            'tires' => 'MG_SC',
            'timekeeper_label' => 'MINI GR.3',
        ],

        '60 MINI GR3 UNDER 10' => [
            'name' => 'Mini GR.3 Under 10',
            'tires' => 'MG_SC',
            'timekeeper_label' => 'MINI GR.3 U.10',
        ],

        // '60 MINI MK' => [
        //     // Deprecated category, all drivers to be moved under 60 Mini Territorial
        //     'name' => '60 Mini Kart',
        //     'description' => 'with engine approval 2010-2014',
        //     'tires' => 'VEGA_MINI',
        // ],

        '60 MINI TDM ROK' => [
            'name' => '60 Mini Rok (TDM)',
            'tires' => 'VEGA_MINI',
            'timekeeper_label' => 'MINIKART TERRITORIALE',
            'enabled' => false,
        ],

        '60 MINI TDM EASY' => [
            'name' => '60 Easy (TDM)',
            'tires' => 'VEGA_MINI',
            'timekeeper_label' => 'MINIKART TERRITORIALE',
            'enabled' => false,
        ],

        '60 MINI TDM FR ROTAX' => [
            'name' => '60 Mini FR Rotax (TDM)',
            'tires' => 'VEGA_MINI',
            'timekeeper_label' => 'MINIKART TERRITORIALE',
            'enabled' => false,
        ],

        '60 MINI TDM X30' => [
            'name' => '60 Mini X30 (TDM)',
            'tires' => 'VEGA_MINI',
            'timekeeper_label' => 'MINIKART TERRITORIALE',
            'enabled' => false,
        ],

        '60 MINI TERR' => [
            'name' => '60cc Territorial',
            'description' => 'For all engine trophies including TDM (e.g. X30, Rok, Rotax, ...)',
            'tires' => 'VEGA_MINI',
            'timekeeper_label' => 'MINIKART TERRITORIALE',
        ],

        '125 JUNIOR TERR' => [
            'name' => '125 Junior Territorial',
            'description' => 'For all engine trophies including TDM (e.g. X30, Rok, Rotax, ...)',
            'tires' => 'VEGA_XH3',
            'timekeeper_label' => '125 TAG JUNIOR',
        ],

        '125 JUNIOR OK' => [
            'name' => '125 OK Junior',
            'tires' => 'VEGA_XH3',
            'timekeeper_label' => '125 OK JUNIOR',
        ],

        '125 JUNIOR OK-N' => [
            'name' => '125 OK-N Junior',
            'tires' => 'VEGA_XH3',
            'timekeeper_label' => '125 OK-N JUNIOR',
        ],

        '125 JUNIOR TDM ROK' => [
            'name' => '125 Junior Rok (TDM)',
            'tires' => 'VEGA_XH3',
            'timekeeper_label' => '125 TAG JUNIOR',
            'enabled' => false,
        ],
        '100 JUNIOR TDM EASY' => [
            'name' => '100 Easy (TDM)',
            'tires' => 'VEGA_XH3',
            'timekeeper_label' => '100 JUNIOR TDM EASY',
        ],
        '125 JUNIOR TDM X30' => [
            'name' => '125 Junior X30 (TDM)',
            'tires' => 'VEGA_XH3',
            'timekeeper_label' => '125 TAG JUNIOR',
            'enabled' => false,
        ],

        '125 SENIOR TERR' => [
            'name' => '125 Senior Territorial',
            'description' => 'For all engine trophies including TDM (e.g. X30, Rok, Rotax, ...)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
        ],

        '125 SENIOR OK' => [
            'name' => '125 OK Senior',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 OK SENIOR',
        ],

        '125 SENIOR OK-N' => [
            'name' => '125 OK-N Senior',
            'tires' => 'VEGA_XH3',
            'timekeeper_label' => '125 OK-N SENIOR',
        ],

        '125 SENIOR TDM ROK' => [
            'name' => '125 Senior Rok (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM SUPEROK' => [
            'name' => '125 Senior Superok (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM ROTAX MAX' => [
            'name' => '125 Senior Rotax Max (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM BMB' => [
            'name' => '125 Senior BMB (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM X30' => [
            'name' => '125 Senior X30 (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM SHIFTER ROK' => [
            'name' => '125 Senior Shifter Rok (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM KGP SHIFTER' => [
            'name' => '125 Senior KGP Shifter (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM X30 SUPER SHIFTER' => [
            'name' => '125 Senior X30 Super Shifter (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM KGP DIRECT DRIVE' => [
            'name' => '125 Senior KGP Direct Drive (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM ROTAX DD2' => [
            'name' => '125 Senior Rotax DD2 (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 SENIOR TDM X30 SUPER' => [
            'name' => '125 Senior X30 Super (TDM)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 TAG SENIOR',
            'enabled' => false,
        ],

        '125 MASTER TERR' => [
            'name' => '125 Master Territorial',
            'description' => 'For all engine trophies including TDM (e.g. X30, Rok, Rotax, ...)',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 MASTER',
        ],

        '125 MASTER OK-N' => [
            'name' => '125 OK-N Over 40',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 OK-N OVER 40',
        ],

        '125 KZ TERR' => [
            'name' => '125 KZ Territorial',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '125 KZ TERR',
        ],

        '125 KZ 2' => [
            'name' => '125 KZ 2',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 KZ 2',
        ],

        '125 KZ 2 UNDER' => [
            'name' => '125 KZ 2 under',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 KZ 2 UNDER',
        ],

        '125 KZ 2 OVER' => [
            'name' => '125 KZ 2 over',
            'tires' => 'MG_SM',
            'timekeeper_label' => '125 KZ 2 OVER',
        ],

        '125 KZ N OVER 25' => [
            'name' => '125 KZ N over 25',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '125 KZ N OVER 25',
        ],

        '125 KZ N OVER 30' => [
            'name' => '125 KZ N over 30',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '125 KZ N OVER 30',
        ],

        '125 KZ N OVER 50' => [
            'name' => '125 KZ N over 50',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '125 KZ N OVER 50',
        ],

        '125 KZ N ROOKIE' => [
            'name' => '125 KZ N rookie',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '125 KZ N ROOKIE',
        ],

        '270 FOURSTROKE' => [
            'name' => '270 Fourstroke (GR1 - GR2)',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '270 FOURSTROKE',
        ],

        '100 LEGEND' => [
            'name' => '100 Legend',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '100 LEGEND',
        ],
        '100 LEGEND OVER 40' => [
            'name' => '100 Legend Over 40',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '100 LEGEND OVER 40',
        ],
        '125 LEGEND' => [
            'name' => '125 Legend',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '125 LEGEND',
        ],
        '125 LEGEND OVER 40' => [
            'name' => '125 Legend Over 40',
            'tires' => 'VEGA_SL4',
            'timekeeper_label' => '125 LEGEND OVER 40',
        ],
    ],

];
