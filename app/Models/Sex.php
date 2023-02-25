<?php

namespace App\Models;

use Illuminate\Support\Str;

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
