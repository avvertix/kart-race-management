<?php

declare(strict_types=1);

namespace App\Models;

enum BonusMode: int
{
    case CREDIT = 10;
    case BALANCE = 20;

    public function localizedName(): string
    {
        return trans("bonus-modes.{$this->name}");
    }

    public function description(): string
    {
        return trans("bonus-modes.description.{$this->name}");
    }
}
