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
        'mechanic' => 'Meccanico',
    ],

    'document' => [
        'identity_document' => 'Documento d\'identità',
        'medical_certificate' => 'Certificato medico',
        'privacy' => 'Informativa privacy',
        'delegation' => 'Delega',
        'parental_authorisation' => 'Autorizzazione dei genitori',
    ],

    'document_description' => [
        'identity_document' => 'Carta d\'identità o passaporto, con il codice fiscale.',
        'medical_certificate' => 'Certificato medico agonistico, valido per tutto l\'anno della licenza.',
        'privacy' => 'Firmata da entrambi i genitori quando il titolare è minorenne.',
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
        'verified' => 'Verificata',
        'forwarded' => 'Presso l\'ente',
        'sent_to_provider' => 'Inviata all\'ente',
        'emitted' => 'Emessa',
        'rejected' => 'Respinta',
    ],

    'request_section' => [
        'licence' => 'Licenza',
        'holder' => 'Titolare',
        'parents' => 'Genitori o tutori',
        'documents' => 'Documenti',
        'forms' => 'Moduli da firmare',
    ],

    'request_payment_status' => [
        'awaiting_price' => 'In attesa dell\'importo',
        'awaiting_payment' => 'Da pagare',
        'payment_submitted' => 'Ricevuta caricata',
        'paid' => 'Pagata',
        'covered' => 'Inclusa in un pacchetto',
    ],

    'request_document_status' => [
        'pending' => 'Da verificare',
        'accepted' => 'Visto',
        'flagged' => 'Da correggere',
    ],

];
