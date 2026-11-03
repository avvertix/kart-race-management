<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderPriced;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPriceSet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetOrderPrice
{
    /**
     * Set the final amount of the Order, in cents. An amount different from the locked Price needs a reason, shown to the User.
     *
     * @throws ValidationException when a payment proof was already uploaded or the reason is missing
     */
    public function __invoke(Order $order, User $organizer, int $amount, ?string $reason = null): Order
    {
        $line = $order->lines()->with('price')->sole();

        $isOverride = ! $line->price->accepts($amount);

        Validator::make(['amount' => $amount, 'price_override_reason' => $reason], [
            'amount' => 'required|integer|min:0',
            'price_override_reason' => [$isOverride ? 'required' : 'nullable', 'string', 'max:1000'],
        ], [
            'price_override_reason.required' => __('Explain why the price differs from :price.', ['price' => $line->price->label()]),
        ])->validate();

        $order = DB::transaction(function () use ($order, $line, $organizer, $amount, $reason, $isOverride) {
            $locked = $order->lockInStatus(
                [OrderStatus::AwaitingPrice, OrderStatus::AwaitingPayment],
                'amount',
                __('The price cannot change after a payment proof is uploaded.'),
            );

            if (! $locked->canBePriced()) {
                throw ValidationException::withMessages([
                    'amount' => __('The price cannot change after a payment proof is uploaded.'),
                ]);
            }

            $line->update(['amount' => $amount]);

            $locked->update([
                'status' => OrderStatus::AwaitingPayment,
                'total' => $amount,
                'price_override_reason' => $isOverride ? $reason : null,
                'priced_by' => $organizer->getKey(),
                'priced_at' => now(),
            ]);

            return $locked;
        });

        OrderPriced::dispatch($order);

        $order->user->notify(new OrderPriceSet($order));

        return $order;
    }
}
