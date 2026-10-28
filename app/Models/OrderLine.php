<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Product in an Order, with the Price locked when the Order was placed.
 *
 * @property int $order_id
 * @property int $product_id
 * @property int $price_id
 * @property string $description
 * @property ?int $amount
 * @property-read Order $order
 * @property-read Product $product
 * @property-read Price $price
 */
class OrderLine extends Model
{
    protected $fillable = [
        'product_id',
        'price_id',
        'description',
        'amount',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Price, $this>
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
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
        ];
    }
}
