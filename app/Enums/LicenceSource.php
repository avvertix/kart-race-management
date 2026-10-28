<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a Licence reached the platform.
 */
enum LicenceSource: string
{
    /**
     * Created by the organizer from a Licence Request.
     */
    case Emitted = 'emitted';

    /**
     * Entered by the User, verified later by the organizer.
     */
    case Declared = 'declared';

    /**
     * The human readable name of the source.
     */
    public function label(): string
    {
        return trans("licences.source.{$this->value}");
    }
}
