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
     * Mark an uploaded document or signed form as viewed, back to review, or flag it with a comment for the requester.
     * The comment reaches the requester once the organizer asks for changes; any other status clears it.
     *
     * @throws ValidationException when the request is not being reviewed or a flag has no comment
     */
    public function __invoke(LicenceRequestDocument $document, User $organizer, LicenceRequestDocumentStatus $status, ?string $note = null): LicenceRequestDocument
    {
        $isFlagged = $status === LicenceRequestDocumentStatus::Flagged;

        if ($isFlagged) {
            Validator::make(['note' => $note], ['note' => 'required|string|max:1000'])->validate();
        }

        return DB::transaction(function () use ($document, $organizer, $status, $isFlagged, $note) {
            $document->licenceRequest->lockForReview(
                LicenceRequestStatus::underReview(),
                $organizer,
                __('Documents can be reviewed only while the request waits for the organizer.'),
            );

            $document->update([
                'status' => $status,
                'note' => $isFlagged ? $note : null,
            ]);

            return $document;
        });
    }
}
