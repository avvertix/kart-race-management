<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequestDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewLicenceRequestDocument
{
    /**
     * Accept an uploaded document or signed form, or flag it with a note for the requester. The flag reaches the requester
     * once the organizer asks for changes.
     *
     * @throws ValidationException when the request is not being reviewed or a flag has no note
     */
    public function __invoke(LicenceRequestDocument $document, User $organizer, bool $accept, ?string $note = null): LicenceRequestDocument
    {
        if (! $accept) {
            Validator::make(['note' => $note], ['note' => 'required|string|max:1000'])->validate();
        }

        return DB::transaction(function () use ($document, $organizer, $accept, $note) {
            $document->licenceRequest->lockForReview(
                LicenceRequestStatus::underReview(),
                $organizer,
                __('Documents can be reviewed only while the request waits for the organizer.'),
            );

            $document->update([
                'status' => $accept ? LicenceRequestDocumentStatus::Accepted : LicenceRequestDocumentStatus::Flagged,
                'note' => $accept ? null : $note,
            ]);

            return $document;
        });
    }
}
