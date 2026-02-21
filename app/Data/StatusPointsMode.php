<?php

declare(strict_types=1);

namespace App\Data;

enum StatusPointsMode: string
{
    case Fixed = 'fixed';
    case Ranked = 'ranked';
}
