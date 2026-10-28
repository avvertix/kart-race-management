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
        'LOCAL_NATIONAL' => 'National',
        'LOCAL_INTERNATIONAL' => 'International',
        'FOREIGN' => 'Foreign',
        'EASYRACE' => 'Easyrace',
        'DAILY' => 'Daily',
    ],

    'competitor' => [
        'LOCAL' => 'Nazional',
        'FOREIGN' => 'Foreign',
    ],

    'provider' => [
        'aci' => 'ACI Sport',
        'asi' => 'ASI',
    ],

    'role' => [
        'driver' => 'Driver',
        'competitor' => 'Competitor',
        'mechanic' => 'Mechanic',
    ],

    'level' => [
        'national' => 'National',
        'international' => 'International',
    ],

    'document_kind' => [
        'upload' => 'Upload',
        'signed_form' => 'Form to sign',
    ],

    'document_scope' => [
        'holder' => 'Holder',
        'each_parent' => 'Each parent or guardian',
        'once' => 'Once per request',
    ],

    'form_template' => [
        'delegation' => 'Delegation',
        'privacy' => 'Privacy',
        'parental_authorisation' => 'Parental authorisation',
        'self_declaration' => 'Self-declaration',
    ],

    'source' => [
        'emitted' => 'Emitted',
        'declared' => 'Declared',
    ],

    'request_status' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'in_review' => 'In review',
        'changes_requested' => 'Changes requested',
        'forwarded' => 'Forwarded to the provider',
        'emitted' => 'Emitted',
        'rejected' => 'Rejected',
    ],

    'request_section' => [
        'licence' => 'Licence',
        'holder' => 'Holder',
        'parents' => 'Parents or guardians',
        'documents' => 'Documents',
        'forms' => 'Forms to sign',
        'payment' => 'Payment',
    ],

    'request_document_status' => [
        'pending' => 'To review',
        'accepted' => 'Accepted',
        'flagged' => 'Needs changes',
    ],

];
