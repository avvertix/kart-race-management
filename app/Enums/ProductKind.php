<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a Product sells.
 */
enum ProductKind: string
{
    case LicenceEmission = 'licence_emission';
    case Enrollment = 'enrollment';
    case Package = 'package';

    /**
     * The kinds as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])
            ->all();
    }

    /**
     * The human readable name of the kind.
     */
    public function label(): string
    {
        return trans("ordering.product_kind.{$this->value}");
    }
}
