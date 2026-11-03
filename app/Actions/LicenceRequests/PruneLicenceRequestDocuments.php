<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PruneLicenceRequestDocuments
{
    /**
     * Delete the documents uploaded with the Licence Request, with their folder, and its payment proofs, files included,
     * and record when.
     * The temporary licence PDF is kept with the Licence.
     */
    public function __invoke(LicenceRequest $licenceRequest): void
    {
        DB::transaction(function () use ($licenceRequest) {
            $licenceRequest->documents()->get()->each(fn (LicenceRequestDocument $document) => $document->deleteWithFile());

            $licenceRequest->payments()->get()->each(fn (Payment $payment) => $payment->deleteWithFile());

            $licenceRequest->update(['documents_pruned_at' => now()]);

            DB::afterCommit(fn () => Storage::disk('licence-documents')->deleteDirectory("licence-requests/{$licenceRequest->ulid}"));
        });
    }
}
