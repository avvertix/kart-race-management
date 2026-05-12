<?php

declare(strict_types=1);

namespace App\Data;

enum WildcardPointsMode: string
{
    case AsOtherDrivers = 'as_other_drivers';
    case FixedPoints = 'fixed_points';
    case RankedFromFirst = 'ranked_from_first';
}
