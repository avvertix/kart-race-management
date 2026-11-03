<?php

declare(strict_types=1);

return [

    'types' => [
        'category' => 'Category',
        'overall' => 'Overall',
        'day' => 'Day',
    ],

    'tie_breakers' => [
        'session_bonus' => 'Session bonus',
        'race_1_best_lap_time' => 'Race 1 best lap',
        'race_2_best_lap_time' => 'Race 2 best lap',
        'qualifying_best_lap_time' => 'Qualifying best lap',
    ],

    'ranking_modes' => [
        'all' => 'All races',
        'best_n' => 'Best N races',
        'specific' => 'Specific races',
    ],

    'wildcard_filters' => [
        'all' => 'All participants',
        'only' => 'Only wildcards',
        'exclude' => 'Exclude wildcards',
    ],

];
