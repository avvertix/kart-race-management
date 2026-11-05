<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementMode;
use App\Enums\ProductGrantKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something a Promotional Package grants once its Order is paid: a licence renewal, an Enrollment or an Entitlement.
 *
 * @property int $product_id
 * @property ProductGrantKind $kind
 * @property ?int $championship_id
 * @property ?EntitlementMode $mode
 * @property ?int $amount
 * @property bool $grants_bib_reservation
 * @property-read Product $product
 * @property-read ?Championship $championship
 */
class ProductGrant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductGrantFactory> */
    use HasFactory;

    protected $fillable = [
        'kind',
        'championship_id',
        'mode',
        'amount',
        'grants_bib_reservation',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Championship, $this>
     */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * What the grant gives, e.g. "Race fees discount: 2 race fees in Championship 2027".
     */
    public function summary(): string
    {
        return match ($this->kind) {
            ProductGrantKind::LicenceEmission => $this->kind->label(),
            ProductGrantKind::Enrollment => __(':grant in :championship', ['grant' => $this->kind->label(), 'championship' => $this->championship?->title])
                .($this->grants_bib_reservation ? ' · '.__('with race number') : ''),
            ProductGrantKind::Entitlement => __(':grant: :amount in :championship', [
                'grant' => $this->kind->label(),
                'amount' => $this->mode?->describe((int) $this->amount),
                'championship' => $this->championship?->title,
            ]),
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ProductGrantKind::class,
            'mode' => EntitlementMode::class,
            'amount' => 'integer',
            'grants_bib_reservation' => 'boolean',
        ];
    }
}
