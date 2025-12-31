<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Describable;

enum RaceType: int implements Describable
{
    /*
     * Races within a championship can have different rules based on external championships or decisions of the organizer
     * To this extend the race type is added to model the different type of the race even if included in
     * a championship that is not of the same type.
     *
     * Although this is probably better to be specified at the championship level it currently creates
     * more difficulties as the organizer is the same and participant bib you be kept across multiple
     * championships at this point of the season
     */

    case LOCAL = 10;
    case NATIONAL = 40;
    case INTERNATIONAL = 50;

    public function localizedName(): string
    {
        return trans("race-type.types.{$this->name}");
    }

    public function description(): string
    {
        return trans("race-type.descriptions.{$this->name}");
    }
}
