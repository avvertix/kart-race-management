<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * A section of the Licence Request form, also the target of a change request.
 */
enum LicenceRequestSection: string
{
    case Licence = 'licence';
    case Holder = 'holder';
    case Parents = 'parents';
    case Documents = 'documents';
    case Forms = 'forms';

    /**
     * The section listing the documents of the given kind.
     */
    public static function forDocumentKind(LicenceDocumentKind $kind): self
    {
        return match ($kind) {
            LicenceDocumentKind::Upload => self::Documents,
            LicenceDocumentKind::SignedForm => self::Forms,
        };
    }

    /**
     * The kind of documents the section lists, null for sections without documents.
     */
    public function documentKind(): ?LicenceDocumentKind
    {
        return match ($this) {
            self::Documents => LicenceDocumentKind::Upload,
            self::Forms => LicenceDocumentKind::SignedForm,
            default => null,
        };
    }

    /**
     * The human readable name of the section.
     */
    public function label(): string
    {
        return trans("licences.request_section.{$this->value}");
    }
}
