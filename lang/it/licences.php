<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Licence Language Lines
    |--------------------------------------------------------------------------
    |
    |
    */

    'driver' => [
        'LOCAL_NATIONAL' => 'Nazionale',
        'LOCAL_INTERNATIONAL' => 'Internazionale',
        'FOREIGN' => 'Estera',
        'EASYRACE' => 'Easyrace',
        'DAILY' => 'Giornaliera',
    ],

    'competitor' => [
        'LOCAL' => 'Nazionale',
        'FOREIGN' => 'Estera',
    ],

    'provider' => [
        'aci' => 'ACI Sport',
        'asi' => 'ASI',
    ],

    'role' => [
        'driver' => 'Conduttore',
        'competitor' => 'Concorrente',
        'mechanic' => 'Meccanico',
    ],

    'level' => [
        'national' => 'Nazionale',
        'international' => 'Internazionale',
    ],

    'document_kind' => [
        'upload' => 'Caricamento',
        'signed_form' => 'Modulo da firmare',
    ],

    'document_scope' => [
        'holder' => 'Titolare',
        'each_parent' => 'Ogni genitore o tutore',
        'once' => 'Una volta per richiesta',
    ],

    'form_template' => [
        'delegation' => 'Delega',
        'privacy' => 'Privacy',
        'parental_authorisation' => 'Autorizzazione dei genitori',
        'self_declaration' => 'Autocertificazione',
    ],

    'source' => [
        'emitted' => 'Emessa',
        'declared' => 'Dichiarata',
    ],

    'request_status' => [
        'draft' => 'Bozza',
        'submitted' => 'Inviata',
        'in_review' => 'In verifica',
        'changes_requested' => 'Modifiche richieste',
        'forwarded' => 'Inoltrata all\'ente',
        'emitted' => 'Emessa',
        'rejected' => 'Respinta',
    ],

    'request_section' => [
        'licence' => 'Licenza',
        'holder' => 'Titolare',
        'parents' => 'Genitori o tutori',
        'documents' => 'Documenti',
        'forms' => 'Moduli da firmare',
        'payment' => 'Pagamento',
    ],

    'request_document_status' => [
        'pending' => 'Da verificare',
        'accepted' => 'Accettato',
        'flagged' => 'Da correggere',
    ],

];
