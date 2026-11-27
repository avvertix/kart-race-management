<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a driver enrolls in a championship with: free or a fixed amount, possibly with a deposit first and the balance
 * due by a date, optionally covering the licence renewal, reserving a race number and granting race fee discounts, and
 * possibly only for some licence categories.
 *
 * @property string $ulid
 * @property int $championship_id
 * @property string $title
 * @property ?string $description
 * @property bool $active
 * @property int $sort
 * @property int $amount
 * @property Currency $currency
 * @property ?int $deposit_amount
 * @property ?CarbonImmutable $balance_due_on
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
        'deposit_amount',
        'balance_due_on',
        'covers_licence_renewal',
        'reserves_race_number',
        'entitlement_mode',
        'entitlement_amount',
        'licence_categories',
    ];

    /**
     * The payment terms of a deposit and a balance due date, null without a deposit. Shared with the Enrollment, which
     * keeps its own copy of the terms.
     */
    public static function describePaymentTerms(Currency $currency, ?int $depositAmount, ?CarbonImmutable $balanceDueOn): ?string
    {
        if ($depositAmount === null || $balanceDueOn === null) {
            return null;
        }

        return (string) __('Deposit :deposit, balance by :date', [
            'deposit' => $currency->format($depositAmount),
            'date' => $balanceDueOn->isoFormat('D MMMM YYYY'),
        ]);
    }

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
     * How the amount is paid when it asks for a deposit first, e.g. "Deposit €300.00, balance by 31 March 2027"; null
     * when it is paid at once.
     */
    public function paymentTermsLabel(): ?string
    {
        return self::describePaymentTerms($this->currency, $this->deposit_amount, $this->balance_due_on);
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
            'deposit_amount' => 'integer',
            'balance_due_on' => 'date',
            'covers_licence_renewal' => 'boolean',
            'reserves_race_number' => 'boolean',
            'entitlement_mode' => EntitlementMode::class,
            'entitlement_amount' => 'integer',
            'licence_categories' => 'array',
        ];
    }
}
