<?php

declare(strict_types=1);

namespace App\Data;

use DateTimeImmutable;
use Spatie\LaravelData\Data;

class BirthData extends Data
{
    public function __construct(
        public DateTimeImmutable $date,
        public ?string $place = null,
    ) {}

    public function hash(): string
    {
        return hash('sha512', $this->date->format('Y-m-d'));
    }
}
