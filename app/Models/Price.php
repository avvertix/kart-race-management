<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The amount charged for a Product in a period, either fixed or a range the organizer settles for each Order.
 *
 * @property int $product_id
 * @property ?int $amount
 * @property ?int $amount_min
 * @property ?int $amount_max
 * @property Currency $currency
 * @property CarbonImmutable $starts_at
 * @property ?CarbonImmutable $ends_at
 * @property-read Product $product
 */
class Price extends Model
{
    /** @use HasFactory<\Database\Factories\PriceFactory> */
    use HasFactory;

    protected $fillable = [
        'amount',
        'amount_min',
        'amount_max',
        'currency',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The order lines that locked this Price.
     *
     * @return HasMany<OrderLine, $this>
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Whether the final amount is set by the organizer for each Order.
     */
    public function isRange(): bool
    {
        return $this->amount === null;
    }

    /**
     * Whether the given amount, in cents, matches the fixed amount or is within the range.
     */
    public function accepts(int $amount): bool
    {
        if (! $this->isRange()) {
            return $amount === $this->amount;
        }

        return $amount >= $this->amount_min && $amount <= $this->amount_max;
    }

    /**
     * Whether the Price applies at the given moment. Both ends are included.
     */
    public function isValidAt(CarbonInterface $date): bool
    {
        return $this->starts_at->lessThanOrEqualTo($date)
            && ($this->ends_at === null || $this->ends_at->greaterThanOrEqualTo($date));
    }

    /**
     * The price as shown to Users, e.g. "€180.00" or "from €230.00 to €310.00".
     */
    public function label(): string
    {
        if (! $this->isRange()) {
            return $this->currency->format($this->amount);
        }

        return __('from :min to :max', [
            'min' => $this->currency->format($this->amount_min),
            'max' => $this->currency->format($this->amount_max),
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'amount_min' => 'integer',
            'amount_max' => 'integer',
            'currency' => Currency::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
