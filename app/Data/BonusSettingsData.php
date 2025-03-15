<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class BonusSettingsData extends Data
{
    public function __construct(
        public ?int $fixed_bonus_amount = null,
    ) {}
}
