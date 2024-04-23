<?php

namespace App\Data;

use App\Models\WildcardStrategy;
use Spatie\LaravelData\Data;

class WildcardSettingsData extends Data
{
    public function __construct(
      public bool $enabled = false,
      public ?WildcardStrategy $strategy = null,
    ) {}
}
