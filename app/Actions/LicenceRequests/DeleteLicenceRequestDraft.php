<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteLicenceRequestDraft
{
    /**
     * Delete a draft with its files.
     *
     * @throws ValidationException when the request is not a draft
     */
    public function __invoke(LicenceRequest $licenceRequest): void
    {
        DB::transaction(function () use ($licenceRequest) {
            $locked = $licenceRequest->lockInStatus(LicenceRequestStatus::Draft, __('Only a draft can be deleted.'));

            $locked->documents->each(fn (LicenceRequestDocument $document) => $document->deleteWithFile());

            $locked->changeRequests()->delete();

            $locked->delete();
        });
    }
}
