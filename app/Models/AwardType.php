<?php

declare(strict_types=1);

namespace App\Models;

enum AwardType: string
{
    case Category = 'category';
    case Overall = 'overall';

    public function localizedName(): string
    {
        return trans("award.types.{$this->value}");
    }
}
