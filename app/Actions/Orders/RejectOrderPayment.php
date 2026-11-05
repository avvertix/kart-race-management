<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\OrderPaymentRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectOrderPayment
{
    /**
     * Reject the submitted payment proof, so the User can upload a new one. The rejected proof is kept, and the price stays as it is.
     *
     * @throws ValidationException when no payment proof awaits confirmation
     */
    public function __invoke(Order $order): Order
    {
        $rejected = DB::transaction(function () use ($order) {
            $locked = $order->lockInStatus([OrderStatus::PaymentSubmitted], 'order', __('The order has no payment to reject.'));

            $locked->update(['status' => OrderStatus::AwaitingPayment]);

            return $locked;
        });

        $rejected->user->notify(new OrderPaymentRejected($rejected));

        return $rejected;
    }
}
