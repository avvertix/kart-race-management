<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Actions\Orders\CancelOrder;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteLicenceRequestDraft
{
    public function __construct(
        private CancelOrder $cancelOrder,
    ) {}

    /**
     * Delete a draft with its files, cancelling its Order.
     *
     * @throws ValidationException when the request is not a draft or its order cannot be cancelled
     */
    public function __invoke(LicenceRequest $licenceRequest): void
    {
        DB::transaction(function () use ($licenceRequest) {
            $locked = $licenceRequest->lockInStatus(LicenceRequestStatus::Draft, __('Only a draft can be deleted.'));

            if ($locked->order !== null) {
                ($this->cancelOrder)($locked->order);
            }

            $locked->documents->each(fn (LicenceRequestDocument $document) => $document->deleteWithFile());

            $locked->changeRequests()->delete();

            $locked->delete();
        });
    }
}
