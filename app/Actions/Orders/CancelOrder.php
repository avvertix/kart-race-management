<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    /**
     * Cancel an Order with no payment proof awaiting confirmation, e.g. when its Draft Licence Request is deleted.
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

            return $locked;
        });
    }
}
