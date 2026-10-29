<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The documents a Licence Request asks for. The same for every Licence Type: the organizer checks they fit the chosen one.
 */
enum LicenceDocument: string
{
    /**
     * Asked for the holder and, when underage, for each parent or guardian entered.
     */
    case IdentityDocument = 'identity_document';

    case MedicalCertificate = 'medical_certificate';

    case Privacy = 'privacy';

    case Delegation = 'delegation';

    case ParentalAuthorisation = 'parental_authorisation';

    /**
     * Whether it is a file the applicant already has, or a form given pre-filled to sign.
     */
    public function kind(): LicenceDocumentKind
    {
        return match ($this) {
            self::IdentityDocument, self::MedicalCertificate => LicenceDocumentKind::Upload,
            self::Privacy, self::Delegation, self::ParentalAuthorisation => LicenceDocumentKind::SignedForm,
        };
    }

    /**
     * Whether it is asked for only when the holder is underage.
     */
    public function isUnderageOnly(): bool
    {
        return $this === self::ParentalAuthorisation;
    }

    /**
     * Whether it is also asked for each parent or guardian of an underage holder.
     */
    public function isAlsoForEachParent(): bool
    {
        return $this === self::IdentityDocument;
    }

    /**
     * The human readable name of the document.
     */
    public function label(): string
    {
        return trans("licences.document.{$this->value}");
    }

    /**
     * A hint shown next to the document, null when none.
     */
    public function description(): ?string
    {
        if (! in_array($this, [self::IdentityDocument, self::MedicalCertificate, self::Privacy], true)) {
            return null;
        }

        return trans("licences.document_description.{$this->value}");
    }

    /**
     * The Blade view that renders the pre-filled form, null for uploads. All forms use a placeholder until the real paperwork is available.
     */
    public function formView(): ?string
    {
        return $this->kind() === LicenceDocumentKind::SignedForm ? 'licence-forms.placeholder' : null;
    }
}
