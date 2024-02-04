<?php

namespace App\Models;

enum BonusType: int
{
    case REGISTRATION_FEE = 10;


    public function localizedName(): string
    {
        return trans("bonus-types.{$this->name}");
    }
}
