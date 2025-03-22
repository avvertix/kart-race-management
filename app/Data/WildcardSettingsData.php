<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\WildcardStrategy;
use Spatie\LaravelData\Data;

class WildcardSettingsData extends Data
{
    public function __construct(
        public bool $enabled = false,
        public ?WildcardStrategy $strategy = null,
        public ?int $requiredBonusAmount = null,
    ) {}
}
