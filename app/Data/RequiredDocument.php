<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\LicenceTypeDocument;

/**
 * A document a Licence Request asks for: a Licence Type document, for the holder, once, or for one of the parents entered.
 */
final readonly class RequiredDocument
{
    public function __construct(
        public LicenceTypeDocument $document,
        public ?int $parentIndex = null,
    ) {}

    /**
     * Identifies the document within the request, e.g. "12" or "12-1" for the second parent.
     */
    public function key(): string
    {
        return $this->parentIndex === null
            ? (string) $this->document->getKey()
            : "{$this->document->getKey()}-{$this->parentIndex}";
    }
}
