<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class PaymentSettingsData extends Data
{
    public function __construct(
        public ?string $bank_account = null,
        public ?string $bank_name = null,
        public ?string $bank_holder = null,
    ) {}
}
