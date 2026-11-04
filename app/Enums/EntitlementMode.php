<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Currency;

/**
 * What a promo code or an Entitlement gives, see docs/explanation/discounts.md.
 */
enum EntitlementMode: string
{
    /**
     * A number of race fees, each one covering the full fee of the chosen category.
     */
    case Credit = 'credit';

    /**
     * An amount of money, in cents, to spend on race fees.
     */
    case Balance = 'balance';

    /**
     * The human readable name of the mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::Credit => __('Race fees'),
            self::Balance => __('Balance to spend on race fees'),
        };
    }

    /**
     * The stored amount of what the organizer entered: a number of race fees, or euros turned into cents.
     */
    public function amountFromInput(string|int|float $value): int
    {
        return $this === self::Balance ? (int) round((float) $value * 100) : (int) $value;
    }

    /**
     * What the given amount means in this mode, e.g. "2 race fees" or "€50.00".
     */
    public function describe(int $amount): string
    {
        return match ($this) {
            self::Credit => trans_choice(':count race fee|:count race fees', $amount, ['count' => $amount]),
            self::Balance => Currency::EUR->format($amount),
        };
    }
}
