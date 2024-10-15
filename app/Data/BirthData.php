<?php

namespace App\Data;

use DateTime;
use Spatie\LaravelData\Data;

class BirthData extends Data
{
    public function __construct(
      public DateTime $date,
      public ?string $place = null,
    ) {}

    public function hash(): string
    {
      return hash('sha512', $this->date->format('Y-m-d'));
    }
}
