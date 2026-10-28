<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where an Order is in its lifecycle, see docs/reference/lifecycles.md#order.
 */
enum OrderStatus: string
{
    case AwaitingPrice = 'awaiting_price';
    case AwaitingPayment = 'awaiting_payment';
    case PaymentSubmitted = 'payment_submitted';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * The statuses as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("ordering.status.{$this->value}");
    }
}
