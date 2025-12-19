<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\DriverLicence;
use Spatie\LaravelData\Data;

class LicenceData extends Data
{
    public function __construct(
        public string $number,
        public ?DriverLicence $type = null,
    ) {}

    /**
     * Get the hash of this licence
     */
    public function hash(): string
    {
        return hash('sha512', mb_trim($this->number));
    }

    /**
     * The short hash that can be used as registration code
     */
    public function shortHash(): string
    {
        return mb_substr($this->hash(), 0, 8);
    }
}
