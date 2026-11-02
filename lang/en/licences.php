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
        'mechanic' => 'Mechanic',
    ],

    'document' => [
        'identity_document' => 'Identity document',
        'medical_certificate' => 'Medical certificate',
        'privacy' => 'Privacy policy',
        'delegation' => 'Delegation',
        'parental_authorisation' => 'Parental authorisation',
    ],

    'document_description' => [
        'identity_document' => 'Identity card or passport, with the fiscal code.',
        'medical_certificate' => 'Competitive sport medical certificate, valid for the whole licence year.',
        'privacy' => 'Signed by both parents when the holder is underage.',
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
    ],

    'request_payment_status' => [
        'awaiting_price' => 'Waiting for the amount',
        'awaiting_payment' => 'To pay',
        'payment_submitted' => 'Proof uploaded',
        'paid' => 'Paid',
        'covered' => 'Covered by a package',
    ],

    'request_document_status' => [
        'pending' => 'To review',
        'accepted' => 'Accepted',
        'flagged' => 'Needs changes',
    ],

];
