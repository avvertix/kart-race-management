<?php

declare(strict_types=1);

namespace App\Data\Results;

use App\Models\RunType;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class RunResultData extends Data
{
    public function __construct(
        public readonly RunType $session,
        public readonly string $title,
        public readonly Collection $results,
    ) {}
}
