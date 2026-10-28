<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceRequestDocumentStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceTypeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenceRequestDocument>
 */
class LicenceRequestDocumentFactory extends Factory
{
    /**
     * Define the model's default state: an uploaded identity card waiting for review.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'licence_request_id' => LicenceRequest::factory(),
            'licence_type_document_id' => LicenceTypeDocument::factory(),
            'parent_index' => null,
            'name' => 'Identity card',
            'kind' => LicenceDocumentKind::Upload,
            'path' => 'licence-requests/document.pdf',
            'original_name' => 'identity-card.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'hash' => hash('sha512', 'document'),
            'status' => LicenceRequestDocumentStatus::Pending,
            'note' => null,
        ];
    }

    /**
     * Flagged by the organizer with a note.
     */
    public function flagged(string $note = 'The photo is blurred.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LicenceRequestDocumentStatus::Flagged,
            'note' => $note,
        ]);
    }
}
