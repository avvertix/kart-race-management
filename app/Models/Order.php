<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use App\Support\Payable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * A request by a User to buy Products, paid by uploading a payment proof the organizer confirms.
 *
 * @property string $ulid
 * @property int $user_id
 * @property ?int $licence_id
 * @property OrderStatus $status
 * @property ?int $total
 * @property Currency $currency
 * @property ?string $price_override_reason
 * @property ?int $priced_by
 * @property ?CarbonImmutable $priced_at
 * @property ?int $confirmed_by
 * @property ?CarbonImmutable $paid_at
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read ?Licence $licence
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderLine> $lines
 * @property-read ?Payment $latestPayment
 */
class Order extends Model implements Payable
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    use HasUlids;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'user_id',
        'licence_id',
        'status',
        'total',
        'currency',
        'price_override_reason',
        'priced_by',
        'priced_at',
        'confirmed_by',
        'paid_at',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the Order pays a licence renewal: a Package including one.
     */
    public function coversLicenceRenewal(): bool
    {
        return $this->grantsOf(ProductGrantKind::LicenceEmission)->isNotEmpty();
    }

    /**
     * The Licence a Package of race fee discounts ordered on its own is for.
     *
     * @return BelongsTo<Licence, $this>
     */
    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * The grants of the kind in the Packages of the Order.
     *
     * @return Collection<int, ProductGrant>
     */
    public function grantsOf(ProductGrantKind $kind): Collection
    {
        return $this->lines()->with('product.grants')->get()
            ->flatMap(fn (OrderLine $line) => $line->product->grants->where('kind', $kind))
            ->values();
    }

    /**
     * Whether the buyer can start the licence renewal the Order covers: paid, with a licence renewal in a Package, and
     * not used by a request yet, a rejected one apart.
     */
    public function canStartLicenceRenewal(): bool
    {
        return $this->status === OrderStatus::Paid
            && $this->grantsOf(ProductGrantKind::LicenceEmission)->isNotEmpty()
            && ! $this->coversARequest();
    }

    /**
     * Whether the Order pays a licence renewal that no Licence Request uses yet, a rejected one apart.
     */
    public function hasUnusedRenewal(): bool
    {
        return $this->coversLicenceRenewal() && ! $this->coversARequest();
    }

    /**
     * The payment proofs, including the rejected ones.
     *
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * @return MorphOne<Payment, $this>
     */
    public function latestPayment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }

    /**
     * The amount to pay, or the locked price range until the organizer sets it.
     */
    public function amountLabel(): ?string
    {
        if ($this->total !== null) {
            return $this->currency->format($this->total);
        }

        return $this->lines->first()?->price->label();
    }

    /**
     * Whether the organizer can still set the amount: no payment proof was ever uploaded, not even a rejected one.
     */
    public function canBePriced(): bool
    {
        return in_array($this->status, [OrderStatus::AwaitingPrice, OrderStatus::AwaitingPayment])
            && $this->payments()->doesntExist();
    }

    /**
     * Lock the Order for the rest of the current transaction, ensuring it is still in one of the given statuses.
     *
     * @param  array<int, OrderStatus>  $statuses
     *
     * @throws ValidationException when the Order moved to another status
     */
    public function lockInStatus(array $statuses, string $errorKey, string $message): self
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if (! in_array($locked->status, $statuses, true)) {
            throw ValidationException::withMessages([$errorKey => $message]);
        }

        return $locked;
    }

    public function lockAwaitingPayment(string $errorKey): self
    {
        $message = $this->status === OrderStatus::AwaitingPrice
            ? __('The organizer has not set the price yet.')
            : __('The order is not awaiting a payment.');

        return $this->lockInStatus([OrderStatus::AwaitingPayment], $errorKey, $message);
    }

    public function markPaymentSubmitted(): void
    {
        $this->update(['status' => OrderStatus::PaymentSubmitted]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'integer',
            'currency' => Currency::class,
            'priced_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Whether a Licence Request not rejected is covered by the Order.
     */
    private function coversARequest(): bool
    {
        return LicenceRequest::query()->where('covering_order_id', $this->getKey())->where('status', '!=', LicenceRequestStatus::Rejected)->exists();
    }
}
