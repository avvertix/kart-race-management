<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The organizer confirmed the payment of an Order.
 */
class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}
}
