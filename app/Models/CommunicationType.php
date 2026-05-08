<?php

declare(strict_types=1);

namespace App\Models;

enum CommunicationType: string
{
    case Communication = 'communication';
    case Penalty = 'penalty';

    public function localizedName(): string
    {
        return trans("communication-type.types.{$this->name}");
    }
}
