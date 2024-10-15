<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
      public string $address,
      public string $city,
      public string $province,
      public string $postal_code,
    ) {}
}
