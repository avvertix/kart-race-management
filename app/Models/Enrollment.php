<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Orders\CancelOrder;
use App\Data\HolderData;
use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceRole;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * A driver Licence, or the open Licence Request that will become one, enrolled in a championship.
 * See docs/reference/lifecycles.md#enrollment.
 *
 * @property string $ulid
 * @property int $championship_id
 * @property ?int $licence_id
 * @property ?int $licence_request_id
 * @property ?int $competitor_licence_id
 * @property int $user_id
 * @property EnrollmentStatus $status
 * @property ?int $order_id
 * @property ?CarbonImmutable $confirmed_at
 * @property ?int $confirmed_by
 * @property ?string $rejection_reason
 * @property CarbonImmutable $created_at
 * @property-read Championship $championship
 * @property-read ?Licence $licence
 * @property-read ?LicenceRequest $licenceRequest
 * @property-read ?Licence $competitorLicence
 * @property-read User $user
 * @property-read ?Order $order
 */
class Enrollment extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'championship_id',
        'licence_id',
        'licence_request_id',
        'competitor_licence_id',
        'user_id',
        'status',
        'order_id',
        'confirmed_at',
        'confirmed_by',
        'rejection_reason',
    ];

    /**
     * Refuse a new Enrollment when the Licence, or the Licence Request, already has an active one in the championship.
     * Call it inside the transaction creating the Enrollment, after locking the championship.
     *
     * @throws ValidationException
     */
    public static function ensureNotEnrolled(Championship $championship, ?Licence $licence, ?LicenceRequest $licenceRequest, ?self $except = null): void
    {
        $isEnrolled = self::query()
            ->where('championship_id', $championship->getKey())
            ->active()
            ->when($except, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->where(fn (Builder $query) => $query
                ->when($licence, fn (Builder $query) => $query->orWhere('licence_id', $licence->getKey()))
                ->when($licenceRequest, fn (Builder $query) => $query->orWhere('licence_request_id', $licenceRequest->getKey())))
            ->exists();

        if ($isEnrolled) {
            throw ValidationException::withMessages(['licence' => __('This licence is already enrolled in the championship.')]);
        }

        if (self::isDriverEnrolled($championship, $licence?->number_hash, $licence->holder ?? $licenceRequest?->holder, $except)) {
            throw ValidationException::withMessages(['licence' => __('The driver is already enrolled in the championship.')]);
        }
    }

    /**
     * Whether the Driver has an active Enrollment in the championship, other than the given one: through a Licence with the
     * same number, or a Licence or Licence Request of the same holder (see HolderData::identityKey()).
     */
    public static function isDriverEnrolled(Championship $championship, ?string $licenceNumberHash, ?HolderData $holder, ?self $except = null): bool
    {
        if ($licenceNumberHash === null && $holder === null) {
            return false;
        }

        $enrollments = self::query()
            ->where('championship_id', $championship->getKey())
            ->active()
            ->when($except, fn (Builder $query) => $query->whereKeyNot($except->getKey()));

        if ($licenceNumberHash !== null && $enrollments->clone()->whereHas('licence', fn (Builder $query) => $query->where('number_hash', $licenceNumberHash))->exists()) {
            return true;
        }

        if ($holder === null) {
            return false;
        }

        return $enrollments
            ->with(['licence', 'licenceRequest'])
            ->get()
            ->contains(fn (self $enrollment) => $enrollment->holder()?->identityKey() === $holder->identityKey());
    }

    /**
     * The Licences that can be the Competitor of the driver in the championship, among those the User manages when given:
     * see isValidCompetitor().
     *
     * @return Collection<int, Licence>
     */
    public static function competitorCandidates(Championship $championship, ?User $user, ?HolderData $driver): Collection
    {
        return Licence::query()
            ->when($user, fn (Builder $query, User $user) => $query->managedBy($user))
            ->competitorIn($championship)
            ->orderBy('id')
            ->get()
            ->filter(fn (Licence $licence) => self::isValidCompetitor($championship, $licence, $driver))
            ->values();
    }

    /**
     * Whether the Licence can be the Competitor of the driver: including the competitor licence, valid in the
     * championship's year, of an adult at the championship start, and not the driver's own.
     */
    public static function isValidCompetitor(Championship $championship, Licence $competitor, ?HolderData $driver): bool
    {
        return $competitor->includes_competitor
            && $competitor->validity_year === $championship->start_at->year
            && ! $championship->isUnderageAtStart($competitor->holder)
            && ($driver === null || $competitor->holder->identityKey() !== $driver->identityKey());
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
     * @return BelongsTo<Licence, $this>
     */
    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    /**
     * @return BelongsTo<LicenceRequest, $this>
     */
    public function licenceRequest(): BelongsTo
    {
        return $this->belongsTo(LicenceRequest::class);
    }

    /**
     * @return BelongsTo<Licence, $this>
     */
    public function competitorLicence(): BelongsTo
    {
        return $this->belongsTo(Licence::class, 'competitor_licence_id');
    }

    /**
     * The User who submitted the Enrollment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The race number reservations picked with the Enrollment.
     *
     * @return HasMany<BibReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(BibReservation::class);
    }

    /**
     * Whether the driver can reserve a race number with the Enrollment: when the championship grants it with enrollment,
     * or when the Package paying the Enrollment does.
     */
    public function grantsBibReservation(): bool
    {
        if ($this->championship->enrollment->grants_bib_reservation) {
            return true;
        }

        return $this->order !== null && $this->order->grantsOf(ProductGrantKind::Enrollment)
            ->contains(fn (ProductGrant $grant) => $grant->championship_id === $this->championship_id && $grant->grants_bib_reservation);
    }

    /**
     * Whether a payment proof of the Enrollment's Order waits for the organizer.
     */
    public function hasPaymentUnderReview(): bool
    {
        return $this->order?->status === OrderStatus::PaymentSubmitted;
    }

    /**
     * Whether the Enrollment has nothing to enroll anymore: the Licence Request it waited for was rejected, so the User
     * picks another Licence.
     */
    public function needsLicence(): bool
    {
        return $this->licence_id === null && ($this->licenceRequest === null || $this->licenceRequest->status === LicenceRequestStatus::Rejected);
    }

    /**
     * The driver: the holder of the Licence, or of the Licence Request while waiting for it.
     */
    public function holder(): ?HolderData
    {
        return $this->licence->holder ?? $this->licenceRequest?->holder;
    }

    /**
     * Whether the driver is underage when the championship starts.
     */
    public function isDriverUnderage(): bool
    {
        $holder = $this->holder();

        return $holder !== null && $this->championship->isUnderageAtStart($holder);
    }

    /**
     * Whether the championship asks for a Competitor Licence for this driver.
     */
    public function requiresCompetitor(): bool
    {
        $holder = $this->holder();

        return $holder !== null && $this->championship->requiresCompetitorFor($holder);
    }

    /**
     * What is still missing before the organizer can confirm the Enrollment, as human readable reasons.
     *
     * @return array<int, string>
     */
    public function missingForConfirmation(): array
    {
        return array_values(array_filter([
            $this->licence === null ? (string) __('The licence request is not emitted yet.') : null,
            $this->licence !== null && ! $this->licence->isVerifiedOrEmitted() ? (string) __('The declared licence is not verified yet.') : null,
            $this->licence !== null && $this->licence->role !== LicenceRole::Driver ? (string) __('The licence is not a driver licence.') : null,
            $this->licence !== null && $this->licence->validity_year !== $this->championship->start_at->year ? (string) __('The licence is not valid in the championship year.') : null,
            $this->order !== null && $this->order->status !== OrderStatus::Paid ? (string) __('The payment is not confirmed yet.') : null,
            $this->requiresCompetitor() && $this->competitor_licence_id === null ? (string) __('The driver is underage and no competitor is named.') : null,
        ]));
    }

    /**
     * Lock the Enrollment for update, refusing it when not in one of the statuses. Call it inside a transaction.
     *
     * @param  array<int, EnrollmentStatus>  $statuses
     *
     * @throws ValidationException
     */
    public function lockInStatus(array $statuses, string $errorKey, string $message): self
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if (! in_array($locked->status, $statuses, true)) {
            throw ValidationException::withMessages([$errorKey => $message]);
        }

        return $locked;
    }

    /**
     * Free what a rejected or withdrawn Enrollment held: release its race numbers and cancel its Order when not paid nor
     * with a payment proof to review. Paid enrollments are refunded outside the platform.
     */
    public function releaseNumbersAndCancelUnpaidOrder(): void
    {
        $this->reservations()->notReleased()->update([
            'status' => BibReservationStatus::Released,
            'released_at' => now(),
        ]);

        if ($this->order !== null && in_array($this->order->status, [OrderStatus::AwaitingPrice, OrderStatus::AwaitingPayment], true)) {
            app(CancelOrder::class)($this->order);
        }
    }

    /**
     * Only the Enrollments that count for their Licence, see EnrollmentStatus::active().
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', EnrollmentStatus::active());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }
}
