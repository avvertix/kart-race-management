<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A postal address, shaped like the residence address of a Participant.
 */
class AddressData extends Data
{
    public function __construct(
        public string $address,
        public string $city,
        public ?string $province,
        public string $postal_code,
    ) {}

    public function __toString(): string
    {
        return collect([$this->address, mb_trim("{$this->postal_code} {$this->city}"), $this->province])
            ->filter()
            ->join(', ');
    }
}
