<?php

declare(strict_types=1);

namespace App\Models;

enum WildcardFilter: string
{
    case All = 'all';
    case OnlyWildcards = 'only';
    case ExcludeWildcards = 'exclude';

    public function localizedName(): string
    {
        return trans("award.wildcard_filters.{$this->value}");
    }
}
