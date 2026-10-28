<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\PlaceOrder;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SwitchLicenceRequestProduct
{
    public function __construct(
        private PlaceOrder $placeOrder,
        private CancelOrder $cancelOrder,
    ) {}

    /**
     * Buy another licence Product with a draft: its Order is cancelled and a new one placed at today's price.
     * With another Licence Type, the licence choice and the uploaded files are cleared, as they belong to the previous type.
     *
     * @throws ValidationException when the request is not a draft, the product sells no licence or the order cannot be cancelled
     */
    public function __invoke(LicenceRequest $licenceRequest, Product $product): LicenceRequest
    {
        if (! $product->sellsLicence()) {
            throw ValidationException::withMessages([
                'product' => __('The product does not include a licence.'),
            ]);
        }

        if ($licenceRequest->product_id === $product->getKey()) {
            return $licenceRequest;
        }

        return DB::transaction(function () use ($licenceRequest, $product) {
            $licenceRequest->lockInStatus(LicenceRequestStatus::Draft, __('The product can only be changed while the request is a draft.'));

            if ($licenceRequest->order !== null) {
                ($this->cancelOrder)($licenceRequest->order);
            }

            $order = ($this->placeOrder)($licenceRequest->user, $product);

            $isAnotherType = $licenceRequest->licence_type_id !== $product->licence_type_id;

            if ($isAnotherType) {
                $licenceRequest->documents->each(fn (LicenceRequestDocument $document) => $document->deleteWithFile());
            }

            $licenceRequest->update([
                'product_id' => $product->getKey(),
                'licence_type_id' => $product->licence_type_id,
                'order_id' => $order->getKey(),
                ...($isAnotherType ? ['level' => null, 'licence_grade_id' => null, 'grade_other' => null] : []),
            ]);

            return $licenceRequest->unsetRelations();
        });
    }
}
