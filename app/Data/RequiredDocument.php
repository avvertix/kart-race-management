<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\LicenceDocument;

/**
 * A document a Licence Request asks for: the holder's, or one of the parents entered.
 */
final readonly class RequiredDocument
{
    public function __construct(
        public LicenceDocument $document,
        public ?int $parentIndex = null,
    ) {}

    /**
     * Identifies the document within the request, e.g. "identity_document" or "identity_document-1" for the second parent.
     */
    public function key(): string
    {
        return $this->parentIndex === null
            ? $this->document->value
            : "{$this->document->value}-{$this->parentIndex}";
    }
}
