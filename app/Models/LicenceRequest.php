<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\HolderData;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceDocumentScope;
use App\Enums\LicenceLevel;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestDocumentStatus;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\DataCollection;

/**
 * A request to emit or renew a licence, made by a User for themselves or someone they act for.
 *
 * @property string $ulid
 * @property int $user_id
 * @property int $product_id
 * @property int $licence_type_id
 * @property ?LicenceLevel $level
 * @property ?int $licence_grade_id
 * @property ?string $grade_other
 * @property LicenceRequestStatus $status
 * @property ?HolderData $holder
 * @property ?DataCollection<int, HolderData> $parents
 * @property ?int $order_id
 * @property ?int $licence_id
 * @property ?CarbonImmutable $submitted_at
 * @property ?CarbonImmutable $forwarded_at
 * @property ?CarbonImmutable $emitted_at
 * @property ?CarbonImmutable $rejected_at
 * @property ?string $rejection_reason
 * @property ?int $reviewed_by
 * @property CarbonImmutable $created_at
 * @property-read User $user
 * @property-read Product $product
 * @property-read LicenceType $licenceType
 * @property-read ?LicenceGrade $grade
 * @property-read ?Order $order
 * @property-read ?Licence $licence
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LicenceRequestDocument> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LicenceRequestChangeRequest> $changeRequests
 */
class LicenceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LicenceRequestFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * The age a holder comes of age, under it parents or guardians are required.
     */
    public const ADULT_AGE = 18;

    protected $hidden = [
        'id',
        'holder',
        'parents',
    ];

    protected $fillable = [
        'user_id',
        'product_id',
        'licence_type_id',
        'level',
        'licence_grade_id',
        'grade_other',
        'status',
        'holder',
        'parents',
        'order_id',
        'licence_id',
        'submitted_at',
        'forwarded_at',
        'emitted_at',
        'rejected_at',
        'rejection_reason',
        'reviewed_by',
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
     * The User who made the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<LicenceType, $this>
     */
    public function licenceType(): BelongsTo
    {
        return $this->belongsTo(LicenceType::class);
    }

    /**
     * @return BelongsTo<LicenceGrade, $this>
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(LicenceGrade::class, 'licence_grade_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
     * The grade picked from the list, or the one written by the User.
     */
    public function gradeName(): ?string
    {
        return $this->licence_grade_id !== null ? $this->grade?->name : $this->grade_other;
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
     * The documents of the given kind the request asks for: holder and once documents, plus one per parent entered.
     * Underage-only documents are asked only for underage holders.
     *
     * @return Collection<int, RequiredDocument>
     */
    public function requiredDocuments(LicenceDocumentKind $kind): Collection
    {
        $isUnderage = $this->isUnderage();
        $parentCount = $isUnderage ? count($this->parentHolders()) : 0;

        return $this->licenceType->documents
            ->filter(fn (LicenceTypeDocument $document) => $document->kind === $kind && ($isUnderage || ! $document->underage_only))
            ->flatMap(fn (LicenceTypeDocument $document) => $document->scope === LicenceDocumentScope::EachParent
                ? collect($parentCount > 0 ? range(0, $parentCount - 1) : [])->map(fn (int $index) => new RequiredDocument($document, $index))
                : [new RequiredDocument($document)])
            ->values();
    }

    /**
     * The uploaded file for a required document, if any.
     */
    public function uploadFor(RequiredDocument $required): ?LicenceRequestDocument
    {
        return $this->documents->first(fn (LicenceRequestDocument $document) => $document->licence_type_document_id === $required->document->getKey()
            && $document->parent_index === $required->parentIndex);
    }

    /**
     * Whether the section has everything a submission needs. Payment is never required.
     */
    public function isSectionComplete(LicenceRequestSection $section): bool
    {
        return match ($section) {
            LicenceRequestSection::Licence => ($this->licenceType->provider !== LicenceProvider::Aci || $this->level !== null)
                && ($this->licence_grade_id !== null || filled($this->grade_other)),
            LicenceRequestSection::Holder => $this->holder !== null,
            LicenceRequestSection::Parents => ! $this->isUnderage() || count($this->parentHolders()) > 0,
            LicenceRequestSection::Documents => $this->hasRequiredUploads(LicenceDocumentKind::Upload),
            LicenceRequestSection::Forms => $this->hasRequiredUploads(LicenceDocumentKind::SignedForm),
            LicenceRequestSection::Payment => true,
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

        return $kind !== null && $this->documents->contains(fn (LicenceRequestDocument $document) => $document->kind === $kind && $document->status === LicenceRequestDocumentStatus::Flagged);
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
     * Payment follows the Order instead.
     */
    public function canEditSection(LicenceRequestSection $section): bool
    {
        return match ($this->status) {
            LicenceRequestStatus::Draft => true,
            LicenceRequestStatus::ChangesRequested => $section === LicenceRequestSection::Payment
                || $this->wasSectionFlagged($section)
                || ! $this->isSectionComplete($section),
            default => false,
        };
    }

    /**
     * Remove the uploads about parents no longer entered, e.g. the second parent was removed or the holder is an adult.
     */
    public function forgetUploadsOfRemovedParents(): void
    {
        $parentCount = $this->isUnderage() ? count($this->parentHolders()) : 0;

        $this->documents()
            ->whereNotNull('parent_index')
            ->where('parent_index', '>=', $parentCount)
            ->get()
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
     * Only the requests made by the given User.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRequestedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->getKey());
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => LicenceLevel::class,
            'status' => LicenceRequestStatus::class,
            'holder' => HolderData::class.':encrypted',
            'parents' => DataCollection::class.':'.HolderData::class.',encrypted',
            'submitted_at' => 'datetime',
            'forwarded_at' => 'datetime',
            'emitted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    private function hasRequiredUploads(LicenceDocumentKind $kind): bool
    {
        return $this->requiredDocuments($kind)
            ->filter(fn (RequiredDocument $required) => $required->document->required)
            ->every(fn (RequiredDocument $required) => $this->uploadFor($required) !== null);
    }
}
