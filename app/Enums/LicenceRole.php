<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The role a licence allows its holder to take in a race.
 */
enum LicenceRole: string
{
    case Driver = 'driver';
    case Competitor = 'competitor';
    case Mechanic = 'mechanic';

    /**
     * The roles as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }

    /**
     * The human readable name of the role.
     */
    public function label(): string
    {
        return trans("licences.role.{$this->value}");
    }
}
