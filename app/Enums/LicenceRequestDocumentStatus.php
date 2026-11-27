<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The organizer's review of an uploaded document.
 */
enum LicenceRequestDocumentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Flagged = 'flagged';

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.request_document_status.{$this->value}");
    }
}
