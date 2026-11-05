<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A right of a Licence to pay less for race fees in one championship: a number of race fees (credits) or an amount to
 * spend (balance). See docs/explanation/discounts.md.
 *
 * @property int $championship_id
 * @property ?int $licence_id
 * @property string $licence_hash
 * @property EntitlementMode $mode
 * @property int $amount
 * @property int $remaining
 * @property ?string $source_type
 * @property ?int $source_id
 * @property ?int $product_grant_id
 * @property CarbonImmutable $created_at
 * @property-read Championship $championship
 * @property-read ?Licence $licence
 * @property-read PromoCode|OrderLine|null $source
 */
class Entitlement extends Model
{
    /** @use HasFactory<\Database\Factories\EntitlementFactory> */
    use HasFactory;

    protected $fillable = [
        'championship_id',
        'licence_id',
        'licence_hash',
        'mode',
        'amount',
        'remaining',
        'source_type',
        'source_id',
        'product_grant_id',
    ];

    /**
     * Give the Entitlements claimed by licence number, and the promo codes behind them, to the Licence with that number,
     * when it is valid in the year of their championship.
     */
    public static function linkTo(Licence $licence): void
    {
        $championships = Championship::query()
            ->whereYear('start_at', $licence->validity_year)
            ->select('id');

        self::query()
            ->whereNull('licence_id')
            ->where('licence_hash', $licence->number_hash)
            ->whereIn('championship_id', $championships)
            ->update(['licence_id' => $licence->getKey()]);

        PromoCode::query()
            ->whereNull('licence_id')
            ->where('licence_hash', $licence->number_hash)
            ->whereIn('championship_id', $championships)
            ->update(['licence_id' => $licence->getKey()]);
    }

    /**
     * @return BelongsTo<Championship, $this>
     */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * @return BelongsTo<Licence, $this>
     */
    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    /**
     * The promo code or the order line the Entitlement comes from.
     *
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The registrations that used the Entitlement, with the discount in the pivot `amount`.
     *
     * @return BelongsToMany<Participant, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'participant_entitlement')
            ->withPivot('amount')
            ->withTimestamps();
    }

    /**
     * Use the Entitlement for a race fee: one credit covers it all, a balance up to what is left. Returns the discount, in
     * cents. Call it on a row locked for update.
     */
    public function consume(int $raceFee): int
    {
        $discount = $this->mode === EntitlementMode::Credit ? $raceFee : min($this->remaining, $raceFee);

        $this->update(['remaining' => $this->remaining - ($this->mode === EntitlementMode::Credit ? 1 : $discount)]);

        return $discount;
    }

    /**
     * Give back the use that gave the discount: one credit, or the discounted amount of a balance. Call it on a row
     * locked for update.
     */
    public function giveBack(int $discount): void
    {
        $this->update(['remaining' => min($this->amount, $this->remaining + ($this->mode === EntitlementMode::Credit ? 1 : $discount))]);
    }

    /**
     * What the registrations used, in the unit of the mode: race fees for credits, cents for a balance.
     */
    public function used(): int
    {
        return $this->mode === EntitlementMode::Credit
            ? $this->participants()->count()
            : (int) $this->participants()->sum('participant_entitlement.amount');
    }

    /**
     * Only the Entitlements with something left.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('remaining', '>', 0);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => EntitlementMode::class,
            'amount' => 'integer',
            'remaining' => 'integer',
        ];
    }
}
