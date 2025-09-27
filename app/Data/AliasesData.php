<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AliasesData extends Data
{
    public function __construct(
        public ?string $name,
        public ?string $category,
        public ?string $bib,
    ) {}

    public function __toString()
    {
        return collect([$this->bib, $this->category, $this->name])
            ->filter()
            ->join(' - ');
    }
}
