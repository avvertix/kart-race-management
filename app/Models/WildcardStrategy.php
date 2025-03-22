<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Wildcard\AttributeWildcardBasedOnBibReservation;
use App\Actions\Wildcard\AttributeWildcardBasedOnBonus;
use App\Actions\Wildcard\AttributeWildcardBasedOnFirstRace;

enum WildcardStrategy: int
{
    /**
     * First non canceled race in championship is used to derive partecipants that can gain points for the championship
     */
    case BASED_ON_FIRST_RACE = 10;

    /**
     * Racers with a reservation based on a bib number can gain points for the championship
     */
    case BASED_ON_BIB_RESERVATION = 20;

    /**
     * Racers with a bonus can gain points for the championship
     */
    case BASED_ON_BONUS = 30;

    public function localizedName(): string
    {
        return trans("wildcard-options.{$this->name}");
    }

    public function resolve()
    {
        return match ($this) {
            self::BASED_ON_BONUS => app()->make(AttributeWildcardBasedOnBonus::class),
            self::BASED_ON_BIB_RESERVATION => app()->make(AttributeWildcardBasedOnBibReservation::class),
            self::BASED_ON_FIRST_RACE => app()->make(AttributeWildcardBasedOnFirstRace::class),
        };
    }
}
