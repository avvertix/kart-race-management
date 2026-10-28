<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The body that issues a licence on the platform. Foreign licences are never managed here.
 */
enum LicenceProvider: string
{
    case Aci = 'aci';
    case Asi = 'asi';

    /**
     * The providers as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $provider) => [$provider->value => $provider->label()])
            ->all();
    }

    /**
     * The human readable name of the provider.
     */
    public function label(): string
    {
        return trans("licences.provider.{$this->value}");
    }
}
