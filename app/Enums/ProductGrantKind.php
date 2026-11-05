<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a Promotional Package grants once paid, see docs/how-to/organizer/set-up-products-and-prices.md.
 */
enum ProductGrantKind: string
{
    /**
     * One licence renewal, started by the buyer from the paid order and covered by it.
     */
    case LicenceEmission = 'licence_emission';

    /**
     * An Enrollment in a championship, with the right to reserve a race number when the grant says so.
     */
    case Enrollment = 'enrollment';

    /**
     * An Entitlement to race fees in a championship, for the Licence of the package.
     */
    case Entitlement = 'entitlement';

    /**
     * The human readable name of the kind.
     */
    public function label(): string
    {
        return match ($this) {
            self::LicenceEmission => __('Licence renewal'),
            self::Enrollment => __('Championship enrollment'),
            self::Entitlement => __('Race fees discount'),
        };
    }
}
