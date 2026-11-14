<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a number used in a past championship was matched to the driver of an Enrollment.
 */
enum PreviousNumberMatch: string
{
    case Licence = 'licence';
    case NameAndBirthDate = 'name_and_birth_date';
    case User = 'user';

    /**
     * The human readable name of the match.
     */
    public function label(): string
    {
        return match ($this) {
            self::Licence => __('same licence number'),
            self::NameAndBirthDate => __('same name and birth date'),
            self::User => __('registered by the same user'),
        };
    }
}
