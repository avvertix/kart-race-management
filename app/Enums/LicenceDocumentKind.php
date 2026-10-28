<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a document required by a Licence Type is provided.
 */
enum LicenceDocumentKind: string
{
    /**
     * A file the applicant already has, e.g. an identity card or a medical certificate.
     */
    case Upload = 'upload';

    /**
     * A form given to the applicant pre-filled with their details, to be signed and uploaded.
     */
    case SignedForm = 'signed_form';

    /**
     * The kinds as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])
            ->all();
    }

    /**
     * The human readable name of the kind.
     */
    public function label(): string
    {
        return trans("licences.document_kind.{$this->value}");
    }
}
