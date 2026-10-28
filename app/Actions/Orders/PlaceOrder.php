<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    /**
     * Create an Order for the Product, locking the Price valid now.
     *
     * @throws ValidationException when the user has no verified email or the product cannot be ordered
     */
    public function __invoke(User $user, Product $product): Order
    {
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'product' => __('Verify your email address before placing an order.'),
            ]);
        }

        $price = $product->orderablePrice();

        if ($price === null) {
            throw ValidationException::withMessages([
                'product' => __('The product is not available at the moment.'),
            ]);
        }

        return DB::transaction(function () use ($user, $product, $price) {
            $order = Order::create([
                'user_id' => $user->getKey(),
                'status' => $price->isRange() ? OrderStatus::AwaitingPrice : OrderStatus::AwaitingPayment,
                'total' => $price->amount,
                'currency' => $price->currency,
            ]);

            $order->lines()->create([
                'product_id' => $product->getKey(),
                'price_id' => $price->getKey(),
                'description' => $product->title,
                'amount' => $price->amount,
            ]);

            return $order;
        });
    }
}
