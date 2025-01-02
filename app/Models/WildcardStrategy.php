<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Wildcard\AttributeWildcardBasedOnFirstRace;

enum WildcardStrategy: int
{
    /**
     * First non canceled race in championship is used to derive partecipants that can gain points for the championship
     */
    case BASED_ON_FIRST_RACE = 10;

    // case BASED_ON_BIB_RESERVATION = 20;

    // case BASED_ON_BONUS = 30;

    public function localizedName(): string
    {
        return trans("wildcard-options.{$this->name}");
    }

    public function resolve()
    {
        return app()->make(AttributeWildcardBasedOnFirstRace::class);
    }
}
