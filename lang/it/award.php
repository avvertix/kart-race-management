<?php

declare(strict_types=1);

return [

    'types' => [
        'category' => 'Categoria',
        'overall' => 'Generale',
        'day' => 'Giornata',
    ],

    'tie_breakers' => [
        'session_bonus' => 'Bonus sessione',
        'race_1_best_lap_time' => 'Miglior giro gara 1',
        'race_2_best_lap_time' => 'Miglior giro gara 2',
        'qualifying_best_lap_time' => 'Miglior giro qualifica',
    ],

    'ranking_modes' => [
        'all' => 'Tutte le gare',
        'best_n' => 'Migliori N gare',
        'specific' => 'Gare specifiche',
    ],

    'wildcard_filters' => [
        'all' => 'Tutti i partecipanti',
        'only' => 'Solo wildcard',
        'exclude' => 'Escludi wildcard',
    ],

];
