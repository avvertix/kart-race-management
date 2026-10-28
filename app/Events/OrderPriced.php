<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The organizer set the final amount of an Order.
 */
class OrderPriced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}
}
