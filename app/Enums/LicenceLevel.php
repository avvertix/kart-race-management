<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\DriverLicence;

/**
 * The level of an ACI licence, which decides the licence category used in race registration.
 */
enum LicenceLevel: string
{
    case National = 'national';
    case International = 'international';

    /**
     * The levels as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $level) => [$level->value => $level->label()])
            ->all();
    }

    /**
     * The human readable name of the level.
     */
    public function label(): string
    {
        return trans("licences.level.{$this->value}");
    }

    /**
     * The driver licence category used in race registration for this level.
     */
    public function raceLicence(): DriverLicence
    {
        return match ($this) {
            self::National => DriverLicence::LOCAL_NATIONAL,
            self::International => DriverLicence::LOCAL_INTERNATIONAL,
        };
    }
}
