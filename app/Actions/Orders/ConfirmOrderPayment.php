<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPaymentConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmOrderPayment
{
    /**
     * Confirm the submitted payment proof, marking the Order paid.
     *
     * @throws ValidationException when no payment proof awaits confirmation
     */
    public function __invoke(Order $order, User $organizer): Order
    {
        $order = DB::transaction(function () use ($order, $organizer) {
            $locked = $order->lockInStatus([OrderStatus::PaymentSubmitted], 'order', __('The order has no payment to confirm.'));

            $locked->update([
                'status' => OrderStatus::Paid,
                'confirmed_by' => $organizer->getKey(),
                'paid_at' => now(),
            ]);

            return $locked;
        });

        OrderPaid::dispatch($order);

        $order->user->notify(new OrderPaymentConfirmed($order));

        return $order;
    }
}
