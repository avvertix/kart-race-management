<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasLicenceType;
use App\Data\HolderData;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocument;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Enums\LicenceRole;
use App\Support\LicenceCatalogue;
use App\Support\Payable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\DataCollection;

/**
 * A request to renew a licence, made by a User for themselves or someone they act for. It is paid on its own, or by the Enrollment that covers it.
 *
 * @property string $ulid
 * @property int $user_id
 * @property ?LicenceProvider $provider
 * @property ?int $validity_year
 * @property ?string $licence_type
 * @property bool $includes_competitor
 * @property ?string $previous_licence_number
 * @property ?string $previous_licence_number_hash
 * @property LicenceRequestStatus $status
 * @property ?HolderData $holder
 * @property ?DataCollection<int, HolderData> $parents
 * @property LicenceRequestPaymentStatus $payment_status
 * @property ?PaymentChannelType $payment_channel
 * @property ?int $amount
 * @property Currency $currency
 * @property ?int $priced_by
 * @property ?CarbonImmutable $priced_at
 * @property ?int $payment_confirmed_by
 * @property ?CarbonImmutable $paid_at
 * @property ?int $covering_enrollment_id
 * @property ?int $licence_id
 * @property ?CarbonImmutable $submitted_at
 * @property ?CarbonImmutable $changes_requested_at
 * @property ?CarbonImmutable $verified_at
 * @property ?CarbonImmutable $forwarded_at
 * @property ?CarbonImmutable $emitted_at
 * @property ?CarbonImmutable $rejected_at
 * @property ?CarbonImmutable $documents_pruned_at
 * @property ?string $rejection_reason
 * @property ?int $reviewed_by
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read ?Payment $latestPayment
 * @property-read ?Licence $licence
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LicenceRequestDocument> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LicenceRequestChangeRequest> $changeRequests
 */
class LicenceRequest extends Model implements Payable
{
    /** @use HasFactory<\Database\Factories\LicenceRequestFactory> */
    use HasFactory;

    use HasLicenceType;
    use HasUlids;

    /**
     * The age a holder comes of age, under it parents or guardians are required.
     */
    public const ADULT_AGE = 18;

    protected $attributes = [
        'includes_competitor' => false,
        'payment_status' => 'awaiting_price',
        'currency' => 'EUR',
    ];

    protected $hidden = [
        'id',
        'holder',
        'parents',
        'previous_licence_number',
        'previous_licence_number_hash',
    ];

    protected $fillable = [
        'user_id',
        'provider',
        'validity_year',
        'licence_type',
        'includes_competitor',
        'previous_licence_number',
        'status',
        'holder',
        'parents',
        'payment_status',
        'payment_channel',
        'amount',
        'currency',
        'priced_by',
        'priced_at',
        'payment_confirmed_by',
        'paid_at',
        'covering_enrollment_id',
        'licence_id',
        'submitted_at',
        'changes_requested_at',
        'verified_at',
        'forwarded_at',
        'emitted_at',
        'rejected_at',
        'documents_pruned_at',
        'rejection_reason',
        'reviewed_by',
    ];

    /**
     * The licence year a request made on the given day is for: that year, or the next one from December, when renewals open.
     */
    public static function validityYearFor(CarbonImmutable $date): int
    {
        $date = $date->setTimezone(config('races.timezone'));

        return $date->month === 12 ? $date->year + 1 : $date->year;
    }

    /**
     * The licence years a request started at the given time, now by default, can be for: the one renewals are open for,
     * and the next one while a championship of that year is open for enrollment.
     *
     * @return array<int, int>
     */
    public static function requestableYears(?CarbonImmutable $at = null): array
    {
        $at ??= now();
        $renewalYear = self::validityYearFor($at);
        $nextYear = $renewalYear + 1;

        $isNextYearOpen = Championship::query()
            ->where('enrollment->enabled', true)
            ->whereYear('start_at', $nextYear)
            ->get()
            ->contains(fn (Championship $championship) => $championship->enrollment->isOpen($at));

        return $isNextYearOpen ? [$renewalYear, $nextYear] : [$renewalYear];
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
     * The User who made the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Let the Enrollment pay the request, as its option covers the licence renewal, when the request's own payment is
     * not made nor covered yet. Call it inside a transaction.
     */
    public function coverWith(Enrollment $enrollment): void
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->covering_enrollment_id !== null || ! in_array($locked->payment_status, [LicenceRequestPaymentStatus::AwaitingPrice, LicenceRequestPaymentStatus::AwaitingPayment], true)) {
            return;
        }

        $locked->update([
            'covering_enrollment_id' => $enrollment->getKey(),
            'payment_status' => LicenceRequestPaymentStatus::Covered,
        ]);

        $this->setRawAttributes($locked->getAttributes(), true);
    }

    /**
     * Return the request to its own payment when the Enrollment covering it no longer does: to be priced by the
     * organizer, or paid at the amount already set. Call it inside a transaction.
     */
    public function uncover(): void
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        $locked->update([
            'covering_enrollment_id' => null,
            'payment_status' => $locked->amount === null ? LicenceRequestPaymentStatus::AwaitingPrice : LicenceRequestPaymentStatus::AwaitingPayment,
        ]);

        $this->setRawAttributes($locked->getAttributes(), true);
    }

    /**
     * The Enrollment whose option covers the renewal.
     *
     * @return BelongsTo<Enrollment, $this>
     */
    public function coveringEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'covering_enrollment_id');
    }

    /**
     * The Enrollments waiting for the request, or that did.
     *
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
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
     * The Licence created on emission.
     *
     * @return BelongsTo<Licence, $this>
     */
    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    /**
     * @return HasMany<LicenceRequestDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(LicenceRequestDocument::class);
    }

    /**
     * @return HasMany<LicenceRequestChangeRequest, $this>
     */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(LicenceRequestChangeRequest::class);
    }

    /**
     * The parents or guardians entered, in order.
     *
     * @return array<int, HolderData>
     */
    public function parentHolders(): array
    {
        return array_values($this->parents?->all() ?? []);
    }

    /**
     * Whether the holder is underage on the request date: the day it was submitted, today for a draft.
     */
    public function isUnderage(): bool
    {
        if ($this->holder === null) {
            return false;
        }

        $requestDate = $this->submitted_at ?? now();

        return CarbonImmutable::parse($this->holder->birth_date)->diffInYears($requestDate) < self::ADULT_AGE;
    }

    /**
     * The sections of the form, parents only for an underage holder.
     *
     * @return array<int, LicenceRequestSection>
     */
    public function sections(): array
    {
        return array_values(array_filter(
            LicenceRequestSection::cases(),
            fn (LicenceRequestSection $section) => $section !== LicenceRequestSection::Parents || $this->isUnderage(),
        ));
    }

    /**
     * The documents of the given kind the request asks for, the same for every Licence Type of a provider: the holder's,
     * plus an identity document for each parent of an underage holder. Underage-only documents are asked only for
     * underage holders, and each provider may skip some, see LicenceDocument::isAskedBy().
     *
     * @return Collection<int, RequiredDocument>
     */
    public function requiredDocuments(LicenceDocumentKind $kind): Collection
    {
        $isUnderage = $this->isUnderage();
        $parentCount = $this->requiredParentCount();

        return collect(LicenceDocument::cases())
            ->filter(fn (LicenceDocument $document) => $document->kind() === $kind
                && $document->isAskedBy($this->provider)
                && ($isUnderage || ! $document->isUnderageOnly()))
            ->flatMap(fn (LicenceDocument $document) => [
                new RequiredDocument($document),
                ...($document->isAlsoForEachParent() && $parentCount > 0
                    ? array_map(fn (int $index) => new RequiredDocument($document, $index), range(0, $parentCount - 1))
                    : []),
            ])
            ->values();
    }

    /**
     * Every document and signed form the request asks for.
     *
     * @return Collection<int, RequiredDocument>
     */
    public function allRequiredDocuments(): Collection
    {
        return $this->requiredDocuments(LicenceDocumentKind::Upload)
            ->merge($this->requiredDocuments(LicenceDocumentKind::SignedForm))
            ->values();
    }

    /**
     * Whether the organizer marked every document and signed form the request asks for as viewed.
     */
    public function isEveryDocumentViewed(): bool
    {
        return $this->allRequiredDocuments()
            ->every(fn (RequiredDocument $required) => $this->uploadFor($required)?->status === LicenceRequestDocumentStatus::Accepted);
    }

    /**
     * The uploaded file for a required document, if any.
     */
    public function uploadFor(RequiredDocument $required): ?LicenceRequestDocument
    {
        return $this->documents->first(fn (LicenceRequestDocument $document) => $document->document === $required->document
            && $document->parent_index === $required->parentIndex);
    }

    /**
     * Whether the section has everything a submission needs.
     */
    public function isSectionComplete(LicenceRequestSection $section): bool
    {
        return match ($section) {
            LicenceRequestSection::Licence => $this->isOfEnabledType() && filled($this->previous_licence_number),
            LicenceRequestSection::Holder => $this->holder !== null,
            LicenceRequestSection::Parents => ! $this->isUnderage() || count($this->parentHolders()) > 0,
            LicenceRequestSection::Documents => $this->hasRequiredUploads(LicenceDocumentKind::Upload),
            LicenceRequestSection::Forms => $this->hasRequiredUploads(LicenceDocumentKind::SignedForm),
        };
    }

    /**
     * The sections still missing something before the request can be submitted.
     *
     * @return array<int, LicenceRequestSection>
     */
    public function missingSections(): array
    {
        return array_values(array_filter(
            $this->sections(),
            fn (LicenceRequestSection $section) => ! $this->isSectionComplete($section),
        ));
    }

    /**
     * @throws ValidationException listing the sections still missing something
     */
    public function ensureNothingIsMissing(): void
    {
        $missing = $this->missingSections();

        if ($missing === []) {
            return;
        }

        throw ValidationException::withMessages([
            'request' => __('Still missing: :sections.', [
                'sections' => collect($missing)->map(fn (LicenceRequestSection $section) => $section->label())->join(', '),
            ]),
        ]);
    }

    /**
     * Mark the open change requests of the section as resolved, once the requester saved it.
     */
    public function resolveChangeRequests(LicenceRequestSection $section): void
    {
        $this->changeRequests()
            ->where('section', $section)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);

        $this->unsetRelation('changeRequests');
    }

    /**
     * Whether the organizer flagged the section: an open change request, or a flagged document or form in it.
     */
    public function isSectionFlagged(LicenceRequestSection $section): bool
    {
        $hasOpenChangeRequest = $this->changeRequests
            ->contains(fn (LicenceRequestChangeRequest $changeRequest) => $changeRequest->section === $section && $changeRequest->resolved_at === null);

        if ($hasOpenChangeRequest) {
            return true;
        }

        $kind = $section->documentKind();

        return $kind !== null && $this->documents->contains(fn (LicenceRequestDocument $document) => $document->document->kind() === $kind && $document->status === LicenceRequestDocumentStatus::Flagged);
    }

    /**
     * Whether the section was flagged by a change request, open or already fixed.
     */
    public function wasSectionFlagged(LicenceRequestSection $section): bool
    {
        return $this->isSectionFlagged($section)
            || $this->changeRequests->contains(fn (LicenceRequestChangeRequest $changeRequest) => $changeRequest->section === $section);
    }

    /**
     * Whether the requester can change the section: any section of a draft; after a change request the flagged ones,
     * and those a fix left incomplete, e.g. parents needed after correcting the holder's birth date.
     */
    public function canEditSection(LicenceRequestSection $section): bool
    {
        return match ($this->status) {
            LicenceRequestStatus::Draft => true,
            LicenceRequestStatus::ChangesRequested => $this->wasSectionFlagged($section)
                || ! $this->isSectionComplete($section),
            default => false,
        };
    }

    /**
     * Remove the uploads the request no longer asks for, e.g. the second parent was removed, the holder is an adult, or
     * the chosen provider needs no delegation.
     */
    public function forgetUploadsNoLongerAskedFor(): void
    {
        $required = $this->allRequiredDocuments();

        $this->documents()
            ->get()
            ->reject(fn (LicenceRequestDocument $document) => $required->contains(
                fn (RequiredDocument $item) => $item->document === $document->document && $item->parentIndex === $document->parent_index,
            ))
            ->each(fn (LicenceRequestDocument $document) => $document->deleteWithFile());

        $this->unsetRelation('documents');
    }

    /**
     * Lock the request for the rest of the current transaction, ensuring it is still in the given status.
     *
     * @throws ValidationException when the request moved to another status
     */
    public function lockInStatus(LicenceRequestStatus $status, string $message): self
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status !== $status) {
            throw ValidationException::withMessages(['request' => $message]);
        }

        return $locked;
    }

    /**
     * Lock the request for an organizer's action, ensuring it is in one of the given statuses. The first action on a
     * submitted request moves it to review and records who reviews it.
     *
     * @param  array<int, LicenceRequestStatus>  $statuses
     *
     * @throws ValidationException when the request is not in one of the statuses
     */
    public function lockForReview(array $statuses, User $organizer, string $message): self
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if (! in_array($locked->status, $statuses, true)) {
            throw ValidationException::withMessages(['request' => $message]);
        }

        if ($locked->status === LicenceRequestStatus::Submitted) {
            $locked->update([
                'status' => LicenceRequestStatus::InReview,
                'reviewed_by' => $organizer->getKey(),
            ]);
        }

        return $locked;
    }

    /**
     * The Licence with the same previous number managed by another User, if any: a hint the request may be for someone else's licence.
     */
    public function previousLicenceOfAnotherUser(): ?Licence
    {
        if ($this->previous_licence_number_hash === null) {
            return null;
        }

        return Licence::query()
            ->where('number_hash', $this->previous_licence_number_hash)
            ->where('provider', $this->provider)
            ->where('user_id', '!=', $this->user_id)
            ->latest('validity_year')
            ->first();
    }

    /**
     * Whether the requester can replace the uploaded file: any file of a draft; after a change request, missing files and
     * the flagged ones, also once replaced, as they keep the organizer's note until reviewed again.
     */
    public function canReplaceUpload(?LicenceRequestDocument $upload): bool
    {
        return match ($this->status) {
            LicenceRequestStatus::Draft => true,
            LicenceRequestStatus::ChangesRequested => $upload === null || $upload->wasFlagged(),
            default => false,
        };
    }

    /**
     * Whether every change request is resolved and no document is still flagged.
     */
    public function hasResolvedAllChanges(): bool
    {
        return collect(LicenceRequestSection::cases())
            ->doesntContain(fn (LicenceRequestSection $section) => $this->isSectionFlagged($section));
    }

    /**
     * Whether the requester still has to pay: the amount is not set yet, or no proof was uploaded for it.
     */
    public function needsPayment(): bool
    {
        return in_array($this->payment_status, [LicenceRequestPaymentStatus::AwaitingPrice, LicenceRequestPaymentStatus::AwaitingPayment], true);
    }

    /**
     * Whether nothing is left to pay: the organizer confirmed the payment, or it is covered by an Enrollment whose option
     * covers the renewal, once that Enrollment's deposit is paid (or its full amount, without a deposit).
     */
    public function isPaymentSettled(): bool
    {
        return match ($this->payment_status) {
            LicenceRequestPaymentStatus::Paid => true,
            LicenceRequestPaymentStatus::Covered => $this->coveringEnrollment?->isDepositPaid() === true,
            default => false,
        };
    }

    /**
     * Whether the organizer can still set the amount: not covered by an Enrollment, and no payment proof ever uploaded.
     */
    public function canBePriced(): bool
    {
        return in_array($this->payment_status, [LicenceRequestPaymentStatus::AwaitingPrice, LicenceRequestPaymentStatus::AwaitingPayment], true)
            && $this->payments()->doesntExist();
    }

    /**
     * The amount to pay, null until the organizer sets it.
     */
    public function amountLabel(): ?string
    {
        return $this->amount === null ? null : $this->currency->format($this->amount);
    }

    public function lockAwaitingPayment(string $errorKey): self
    {
        $locked = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->payment_status !== LicenceRequestPaymentStatus::AwaitingPayment) {
            throw ValidationException::withMessages([
                $errorKey => $locked->payment_status === LicenceRequestPaymentStatus::AwaitingPrice
                    ? __('The organizer has not set the amount yet.')
                    : __('The request is not awaiting a payment.'),
            ]);
        }

        if ($locked->status->isClosed()) {
            throw ValidationException::withMessages([$errorKey => __('The request is closed.')]);
        }

        return $locked;
    }

    public function markPaymentSubmitted(): void
    {
        $this->update(['payment_status' => LicenceRequestPaymentStatus::PaymentSubmitted]);
    }

    /**
     * Only the requests made by the given User.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRequestedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->getKey());
    }

    /**
     * Only the requests the organizer lists by default, see LicenceRequestStatus::toProcess().
     *
     * @param  Builder<self>  $query
     */
    public function scopeToProcess(Builder $query): void
    {
        $query->whereIn('status', LicenceRequestStatus::toProcess());
    }

    /**
     * Only the requests not emitted or rejected yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [LicenceRequestStatus::Emitted, LicenceRequestStatus::Rejected]);
    }

    /**
     * Only the requests an Enrollment in the championship can wait for: drafts included, not emitted nor rejected, for a
     * driver licence valid in its year, from any provider.
     *
     * @param  Builder<self>  $query
     */
    public function scopeEnrollableIn(Builder $query, Championship $championship): void
    {
        $query->open()
            ->where('validity_year', $championship->start_at->year)
            ->where(fn (Builder $query) => $query->whereNull('licence_type')->orWhere(fn (Builder $query) => $query->forDriverLicence()));
    }

    /**
     * Only the requests for a driver Licence Type the instance handles, retired types included.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForDriverLicence(Builder $query): void
    {
        $query->where(function (Builder $query) {
            foreach (LicenceProvider::cases() as $provider) {
                $query->orWhere(fn (Builder $query) => $query
                    ->where('provider', $provider)
                    ->whereIn('licence_type', LicenceCatalogue::types($provider, withInactive: true)->where('role', LicenceRole::Driver)->pluck('code')->all()));
            }
        });
    }

    /**
     * Only the requests whose documents are past the retention period and not deleted yet: emitted ones once the year
     * after the Licence's validity year ends, in the races' timezone, and rejected ones 12 months after rejection.
     *
     * @param  Builder<self>  $query
     */
    public function scopePastDocumentRetention(Builder $query): void
    {
        $now = now();
        $lastExpiredValidityYear = $now->setTimezone(config('races.timezone'))->year - 2;

        $query
            ->whereNull('documents_pruned_at')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('status', LicenceRequestStatus::Emitted)
                    ->whereHas('licence', fn (Builder $query) => $query->where('validity_year', '<=', $lastExpiredValidityYear)))
                ->orWhere(fn (Builder $query) => $query
                    ->where('status', LicenceRequestStatus::Rejected)
                    ->where('rejected_at', '<=', $now->subYear())));
    }

    protected static function booted(): void
    {
        static::saving(function (LicenceRequest $licenceRequest) {
            if ($licenceRequest->isDirty('previous_licence_number')) {
                $licenceRequest->previous_licence_number_hash = $licenceRequest->previous_licence_number === null
                    ? null
                    : Licence::hashNumber($licenceRequest->previous_licence_number);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => LicenceProvider::class,
            'validity_year' => 'integer',
            'includes_competitor' => 'boolean',
            'previous_licence_number' => 'encrypted',
            'status' => LicenceRequestStatus::class,
            'holder' => HolderData::class.':encrypted',
            'parents' => DataCollection::class.':'.HolderData::class.',encrypted',
            'payment_status' => LicenceRequestPaymentStatus::class,
            'payment_channel' => PaymentChannelType::class,
            'amount' => 'integer',
            'currency' => Currency::class,
            'priced_at' => 'datetime',
            'paid_at' => 'datetime',
            'submitted_at' => 'datetime',
            'changes_requested_at' => 'datetime',
            'verified_at' => 'datetime',
            'forwarded_at' => 'datetime',
            'emitted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'documents_pruned_at' => 'datetime',
        ];
    }

    /**
     * The parents or guardians whose identity document is asked for: those entered for an underage holder, at least one
     * even before they are entered, so their document can be uploaded in any order.
     */
    private function requiredParentCount(): int
    {
        return $this->isUnderage() ? max(1, count($this->parentHolders())) : 0;
    }

    private function hasRequiredUploads(LicenceDocumentKind $kind): bool
    {
        return $this->requiredDocuments($kind)
            ->every(fn (RequiredDocument $required) => $this->uploadFor($required) !== null);
    }
}
