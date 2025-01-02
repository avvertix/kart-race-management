<?php

declare(strict_types=1);

namespace App\Models;

enum Sex: int
{
    case MALE = 10;
    case FEMALE = 20;
    case UNSPECIFIED = 30;

    public function localizedName(): string
    {
        return trans("sex-options.{$this->name}");
    }
}
