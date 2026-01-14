<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\BonusMode;
use Spatie\LaravelData\Data;

class BonusSettingsData extends Data
{
    public function __construct(
        public ?int $fixed_bonus_amount = null,
        public ?BonusMode $bonus_mode = null,
    ) {}
}
