<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Orders\GrantPackages;
use App\Events\OrderPaid;

class GrantPackagesWhenOrderPaid
{
    public function __construct(private GrantPackages $grantPackages) {}

    /**
     * Grant the Packages of the paid Order, when their Licence is already known.
     */
    public function handle(OrderPaid $event): void
    {
        ($this->grantPackages)($event->order);
    }
}
