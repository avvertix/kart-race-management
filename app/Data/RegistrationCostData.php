<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class RegistrationCostData extends Data
{
    public function __construct(
        public int $registration_cost,
        public ?int $tire_cost = null,
        public ?string $tire_model = null,
        public ?int $discount = null,
    ) {}

    public function total(): int
    {
        return $this->registration_cost + ($this->tire_cost ?? 0) - abs($this->discount ?? 0);
    }

    public function details(): Collection
    {
        return $order = collect([
            __('Race fee') => $this->registration_cost,
        ])->merge(collect([
            __('Tires (:model)', ['model' => $this->tire_model]) => $this->tire_cost,
            __('Discount') => abs($this->discount ?? 0) * -1,
        ])->filter())->merge([
            __('Total') => $this->total(),
        ]);
    }
}
