<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductGrantKind;
use App\Enums\ProductKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something sold to Users: a championship enrollment or a promotional package. Licence renewals are not Products.
 *
 * @property string $ulid
 * @property ProductKind $kind
 * @property string $title
 * @property ?string $description
 * @property ?int $championship_id
 * @property bool $active
 * @property-read ?Championship $championship
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
     * What a Package grants once its Order is paid.
     *
     * @return HasMany<ProductGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(ProductGrant::class);
    }

    /**
     * The active Packages offered when enrolling in the championship: those including an Enrollment in it and no licence
     * renewal, which enrolls through the covered Licence Request instead.
     *
     * @param  Builder<self>  $query
     */
    public function scopeEnrollingIn(Builder $query, Championship $championship): void
    {
        $query->active()
            ->where('kind', ProductKind::Package)
            ->whereHas('grants', fn (Builder $query) => $query
                ->where('kind', ProductGrantKind::Enrollment)
                ->where('championship_id', $championship->getKey()))
            ->whereDoesntHave('grants', fn (Builder $query) => $query->where('kind', ProductGrantKind::LicenceEmission));
    }

    /**
     * The championship whose enrollment page sells the Package, when it includes an Enrollment and no licence renewal:
     * ordered elsewhere, nothing would say which Licence it enrolls.
     */
    public function enrollmentChampionship(): ?Championship
    {
        if ($this->kind !== ProductKind::Package || $this->grants->contains('kind', ProductGrantKind::LicenceEmission)) {
            return null;
        }

        return $this->grants->firstWhere('kind', ProductGrantKind::Enrollment)?->championship;
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
