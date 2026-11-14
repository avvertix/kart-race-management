<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a BIB Reservation is in its lifecycle, see docs/reference/lifecycles.md#bib-reservation.
 */
enum BibReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Released = 'released';

    /**
     * The human readable name of the status.
     */
    public function label(): string
    {
        return trans("licences.bib_reservation_status.{$this->value}");
    }

    /**
     * Whether the reservation still holds the number: pending and confirmed ones do, released ones don't.
     */
    public function holdsTheNumber(): bool
    {
        return $this !== self::Released;
    }
}
