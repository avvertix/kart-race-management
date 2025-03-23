<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Describable;

/**
 * Payments can be received through different channels, e.g. bank transfer, credit card, or cash.
 */
enum PaymentChannelType: int implements Describable
{
    case CASH = 10;
    case BANK_TRANSFER = 20;
    case CREDIT_CARD = 30;

    public function localizedName(): string
    {
        return trans("payment-channels.type.{$this->name}");
    }

    public function description(): string
    {
        return trans("payment-channels.description.{$this->name}");
    }
}
