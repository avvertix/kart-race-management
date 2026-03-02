<?php

declare(strict_types=1);

namespace App\Models;

enum AwardRankingMode: string
{
    case All = 'all';
    case BestN = 'best_n';
    case SpecificRaces = 'specific';

    public function localizedName(): string
    {
        return trans("award.ranking_modes.{$this->value}");
    }
}
