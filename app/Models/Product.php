<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something sold to Users: a licence emission or renewal, a championship enrollment or a promotional package.
 *
 * @property string $ulid
 * @property ProductKind $kind
 * @property string $title
 * @property ?string $description
 * @property ?int $championship_id
 * @property ?int $licence_type_id
 * @property bool $active
 * @property-read ?Championship $championship
 * @property-read ?LicenceType $licenceType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Price> $prices
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    use HasUlids;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'kind',
        'title',
        'description',
        'championship_id',
        'licence_type_id',
        'active',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return BelongsTo<Championship, $this>
     */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * @return BelongsTo<LicenceType, $this>
     */
    public function licenceType(): BelongsTo
    {
        return $this->belongsTo(LicenceType::class);
    }

    /**
     * @return HasMany<Price, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class)->orderBy('starts_at');
    }

    /**
     * The Price valid now. When periods overlap the most recently started wins, e.g. an early-bird price.
     */
    public function currentPrice(): ?Price
    {
        $now = now();

        return $this->prices
            ->filter(fn (Price $price) => $price->isValidAt($now))
            ->sortByDesc(fn (Price $price) => $price->starts_at->getTimestamp())
            ->first();
    }

    /**
     * Whether ordering it starts a Licence Request: a licence emission, or a Package including a licence.
     */
    public function sellsLicence(): bool
    {
        return $this->licence_type_id !== null;
    }

    /**
     * The Price an Order placed now would lock, null when the Product is inactive or has no current Price.
     */
    public function orderablePrice(): ?Price
    {
        return $this->active ? $this->currentPrice() : null;
    }

    /**
     * Only the products offered to Users.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ProductKind::class,
            'active' => 'boolean',
        ];
    }
}
