<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view all orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('order:list');
    }

    /**
     * Determine whether the user can view the order. Drivers only see their own.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->hasPermission('order:list')) {
            return $user->hasPermission('order:view');
        }

        return $user->hasPermission('order:view') && $order->user_id === $user->getKey();
    }

    /**
     * Determine whether the user can order a Product.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('order:create');
    }

    /**
     * Determine whether the user can set the price and confirm or reject the payment.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('order:update');
    }

    /**
     * Determine whether the user can upload a payment proof for their order.
     */
    public function pay(User $user, Order $order): bool
    {
        return $user->hasPermission('order:view') && $order->user_id === $user->getKey();
    }
}
