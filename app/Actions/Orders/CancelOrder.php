<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\LicenceRequest;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    /**
     * Cancel an Order with no payment proof awaiting confirmation, e.g. when its Draft Licence Request is deleted. The open
     * Licence Requests it covered go back to their own payment, to be priced by the organizer when not priced yet.
     *
     * @throws ValidationException when the order is paid, has a payment awaiting confirmation or is already cancelled
     */
    public function __invoke(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = $order->lockInStatus(
                [OrderStatus::AwaitingPrice, OrderStatus::AwaitingPayment],
                'order',
                __('Only an order awaiting its price or payment can be cancelled.'),
            );

            $locked->update(['status' => OrderStatus::Cancelled]);

            LicenceRequest::query()
                ->where('covering_order_id', $locked->getKey())
                ->open()
                ->where('payment_status', LicenceRequestPaymentStatus::Covered)
                ->get()
                ->each(fn (LicenceRequest $licenceRequest) => $licenceRequest->update([
                    'covering_order_id' => null,
                    'payment_status' => $licenceRequest->amount === null ? LicenceRequestPaymentStatus::AwaitingPrice : LicenceRequestPaymentStatus::AwaitingPayment,
                ]));

            return $locked;
        });
    }
}
