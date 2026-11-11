<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\HolderData;
use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceRole;
use App\Enums\PaymentStatus;
use App\Support\Payable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @property ?int $enrollment_option_id
 * @property int $amount
 * @property Currency $currency
 * @property ?int $deposit_amount
 * @property ?CarbonImmutable $balance_due_on
 * @property EnrollmentPaymentStatus $payment_status
 * @property ?CarbonImmutable $paid_at
 * @property ?int $payment_confirmed_by
 * @property ?CarbonImmutable $confirmed_at
 * @property ?int $confirmed_by
 * @property ?string $rejection_reason
 * @property CarbonImmutable $created_at
 * @property-read Championship $championship
 * @property-read ?Licence $licence
 * @property-read ?LicenceRequest $licenceRequest
 * @property-read ?Licence $competitorLicence
 * @property-read User $user
 * @property-read ?EnrollmentOption $option
 */
class Enrollment extends Model implements Payable
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    use HasUlids;

    protected $attributes = [
        'amount' => 0,
        'currency' => 'EUR',
        'payment_status' => 'not_due',
    ];

    protected $fillable = [
        'championship_id',
        'licence_id',
        'licence_request_id',
        'competitor_licence_id',
        'user_id',
        'status',
        'confirmed_at',
        'confirmed_by',
        'rejection_reason',
        'enrollment_option_id',
        'amount',
        'currency',
        'deposit_amount',
        'balance_due_on',
        'payment_status',
        'paid_at',
        'payment_confirmed_by',
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
     * The Enrollment Option the driver chose, null when enrolled by the organizer.
     *
     * @return BelongsTo<EnrollmentOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(EnrollmentOption::class, 'enrollment_option_id');
    }

    /**
     * The payment records: proofs uploaded by the driver, rejected ones included, and payments recorded by the organizer.
     *
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * @throws ValidationException when the Enrollment is closed or not awaiting a payment
     */
    public function lockAwaitingPayment(string $errorKey): self
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if (! in_array($locked->payment_status, [EnrollmentPaymentStatus::AwaitingPayment, EnrollmentPaymentStatus::DepositPaid], true) || ! $locked->status->isActive()) {
            throw ValidationException::withMessages([$errorKey => __('The enrollment is not awaiting a payment.')]);
        }

        return $locked;
    }

    /**
     * Update the payment status once a proof is uploaded, see refreshPaymentStatus().
     */
    public function markPaymentSubmitted(): void
    {
        $this->refreshPaymentStatus();
    }

    /**
     * The sum of the confirmed payment records, in cents.
     */
    public function paidAmount(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->where('status', PaymentStatus::Confirmed)->sum('amount');
        }

        return (int) $this->payments()->confirmed()->sum('amount');
    }

    /**
     * What is still to pay, in cents: never below zero, an amount paid in excess is refunded outside the platform.
     */
    public function balance(): int
    {
        return max(0, $this->amount - $this->paidAmount());
    }

    /**
     * Set the payment status from the amount and the payment records: nothing due, paid once the confirmed records reach
     * the amount, a proof waiting for the organizer, the deposit paid, or awaiting payment. Paid keeps the day it was
     * first reached.
     */
    public function refreshPaymentStatus(): void
    {
        $this->unsetRelation('payments');

        $paid = $this->paidAmount();

        $status = match (true) {
            $this->amount === 0 && $paid === 0 => EnrollmentPaymentStatus::NotDue,
            $paid >= $this->amount => EnrollmentPaymentStatus::Paid,
            $this->payments()->submitted()->exists() => EnrollmentPaymentStatus::PaymentSubmitted,
            $this->deposit_amount !== null && $paid >= $this->deposit_amount => EnrollmentPaymentStatus::DepositPaid,
            default => EnrollmentPaymentStatus::AwaitingPayment,
        };

        $this->update([
            'payment_status' => $status,
            'paid_at' => $status === EnrollmentPaymentStatus::Paid ? ($this->paid_at ?? now()) : null,
        ]);
    }

    /**
     * Whether the Enrollment's payment is settled: nothing due, or paid in full.
     */
    public function isPaymentSettled(): bool
    {
        return $this->payment_status->isSettled();
    }

    /**
     * The deposit and balance due date copied from the option when enrolling, e.g. "Deposit €300.00, balance by
     * 31 March 2027"; null without a deposit.
     */
    public function paymentTermsLabel(): ?string
    {
        return EnrollmentOption::describePaymentTerms($this->currency, $this->deposit_amount, $this->balance_due_on);
    }

    /**
     * Whether enough is paid for the Enrollment to get what its option includes: the deposit, or the full amount when
     * there is none. True when nothing is due.
     */
    public function isDepositPaid(): bool
    {
        return $this->amount === 0 || $this->paidAmount() >= ($this->deposit_amount ?? $this->amount);
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
     * Whether the driver can reserve a race number with the Enrollment: when its option includes one.
     */
    public function grantsBibReservation(): bool
    {
        return $this->option?->reserves_race_number === true;
    }

    /**
     * Whether a payment proof of the Enrollment waits for the organizer.
     */
    public function hasPaymentUnderReview(): bool
    {
        return $this->payment_status === EnrollmentPaymentStatus::PaymentSubmitted;
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
            ! $this->isDepositPaid() ? (string) __('The payment is not confirmed yet.') : null,
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
     * Free what a rejected or withdrawn Enrollment held: release its race numbers and return the open Licence Requests
     * it covered to their own payment. Paid enrollments are refunded outside the platform, and a request they covered
     * then asks for its own payment. A race fee discount already granted stays with the Licence.
     */
    public function releaseNumbersAndCoverage(): void
    {
        $this->reservations()->notReleased()->update([
            'status' => BibReservationStatus::Released,
            'released_at' => now(),
        ]);

        LicenceRequest::query()
            ->where('covering_enrollment_id', $this->getKey())
            ->open()
            ->get()
            ->each(fn (LicenceRequest $licenceRequest) => $licenceRequest->uncover());
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
            'amount' => 'integer',
            'currency' => Currency::class,
            'deposit_amount' => 'integer',
            'balance_due_on' => 'date',
            'payment_status' => EnrollmentPaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
