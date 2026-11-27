<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenceDocument;
use App\Enums\LicenceRequestDocumentStatus;
use App\Models\LicenceRequest;
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
            'document' => LicenceDocument::IdentityDocument,
            'parent_index' => null,
            'path' => 'licence-requests/document.pdf',
            'original_name' => 'identity-card.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'hash' => hash('sha512', 'document'),
            'uploaded_at' => now(),
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
