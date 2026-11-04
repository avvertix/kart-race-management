<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a Promo Code is in its lifecycle, see docs/reference/lifecycles.md#promo-code.
 */
enum PromoCodeStatus: string
{
    case Unclaimed = 'unclaimed';
    case Claimed = 'claimed';
    case Revoked = 'revoked';

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.promo_code_status.{$this->value}");
    }
}
