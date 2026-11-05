<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a driver enrolls in a championship with: free or a fixed amount, optionally covering the licence renewal,
 * reserving a race number and granting race fee discounts, and possibly only for some licence categories.
 *
 * @property string $ulid
 * @property int $championship_id
 * @property string $title
 * @property ?string $description
 * @property bool $active
 * @property int $sort
 * @property int $amount
 * @property Currency $currency
 * @property bool $covers_licence_renewal
 * @property bool $reserves_race_number
 * @property ?EntitlementMode $entitlement_mode
 * @property ?int $entitlement_amount
 * @property ?array<int, int> $licence_categories
 * @property-read Championship $championship
 */
class EnrollmentOption extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentOptionFactory> */
    use HasFactory;

    use HasUlids;

    protected $attributes = [
        'active' => true,
        'sort' => 0,
        'amount' => 0,
        'currency' => 'EUR',
        'covers_licence_renewal' => false,
        'reserves_race_number' => false,
    ];

    protected $fillable = [
        'title',
        'description',
        'active',
        'sort',
        'amount',
        'currency',
        'covers_licence_renewal',
        'reserves_race_number',
        'entitlement_mode',
        'entitlement_amount',
        'licence_categories',
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
     * Whether enrolling with the option is free.
     */
    public function isFree(): bool
    {
        return $this->amount === 0;
    }

    /**
     * Whether the option is offered to a licence of the category; any category when the option names none. A licence
     * of unknown category (a request whose type isn't chosen yet, or the renewal of a licence not on the platform) is
     * offered every option, and the organizer can move the Enrollment to the right one before confirming its payment.
     */
    public function isFor(?DriverLicence $category): bool
    {
        $categories = $this->licence_categories ?? [];

        return $category === null || $categories === [] || in_array($category->value, $categories, true);
    }

    /**
     * The licence categories the option is limited to, empty for any.
     *
     * @return array<int, DriverLicence>
     */
    public function licenceCategories(): array
    {
        return array_values(array_filter(array_map(fn (int $value) => DriverLicence::tryFrom($value), $this->licence_categories ?? [])));
    }

    /**
     * The amount as shown to drivers, "Free" when nothing is due.
     */
    public function amountLabel(): string
    {
        return $this->isFree() ? (string) __('Free') : $this->currency->format($this->amount);
    }

    /**
     * Only the options offered to drivers.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOffered(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Only the options offered to a licence of the category, see isFor().
     *
     * @param  Builder<self>  $query
     */
    public function scopeOfferedFor(Builder $query, ?DriverLicence $category): void
    {
        $query->offered()->when($category, fn (Builder $query, DriverLicence $category) => $query->where(fn (Builder $query) => $query
            ->whereNull('licence_categories')
            ->orWhereJsonLength('licence_categories', 0)
            ->orWhereJsonContains('licence_categories', $category->value)));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort' => 'integer',
            'amount' => 'integer',
            'currency' => Currency::class,
            'covers_licence_renewal' => 'boolean',
            'reserves_race_number' => 'boolean',
            'entitlement_mode' => EntitlementMode::class,
            'entitlement_amount' => 'integer',
            'licence_categories' => 'array',
        ];
    }
}
