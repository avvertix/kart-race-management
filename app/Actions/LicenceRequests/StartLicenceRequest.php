<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Actions\Orders\PlaceOrder;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartLicenceRequest
{
    public function __construct(
        private PlaceOrder $placeOrder,
    ) {}

    /**
     * Start a draft Licence Request for a licence Product, plain or Package, placing its Order.
     *
     * @throws ValidationException when the product sells no licence or cannot be ordered
     */
    public function __invoke(User $user, Product $product): LicenceRequest
    {
        if (! $product->sellsLicence()) {
            throw ValidationException::withMessages([
                'product' => __('The product does not include a licence.'),
            ]);
        }

        return DB::transaction(function () use ($user, $product) {
            $order = ($this->placeOrder)($user, $product);

            return LicenceRequest::create([
                'user_id' => $user->getKey(),
                'product_id' => $product->getKey(),
                'licence_type_id' => $product->licence_type_id,
                'status' => LicenceRequestStatus::Draft,
                'parents' => [],
                'order_id' => $order->getKey(),
            ]);
        });
    }
}
