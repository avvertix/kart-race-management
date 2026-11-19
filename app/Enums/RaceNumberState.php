<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What the race number picker shows for a Championship BIB, see docs/reference/race-numbers.md.
 */
enum RaceNumberState: string
{
    case Free = 'free';
    case Reserved = 'reserved';
    case Used = 'used';

    /**
     * The human readable name of the state.
     */
    public function label(): string
    {
        return match ($this) {
            self::Free => __('free'),
            self::Reserved => __('reserved'),
            self::Used => __('already used'),
        };
    }
}
