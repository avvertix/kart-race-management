<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasLicenceType;
use App\Data\HolderData;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Enums\LicenceSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * A licence held by a person and managed by a User, emitted on the platform or declared by the User.
 *
 * @property string $ulid
 * @property int $user_id
 * @property LicenceProvider $provider
 * @property string $licence_type
 * @property bool $includes_competitor
 * @property LicenceRole $role
 * @property ?DriverLicence $race_licence_category
 * @property string $number
 * @property string $number_hash
 * @property int $validity_year
 * @property HolderData $holder
 * @property LicenceSource $source
 * @property ?CarbonImmutable $verified_at
 * @property ?int $verified_by
 * @property ?string $temporary_licence_path
 * @property ?int $licence_request_id
 * @property-read User $user
 * @property-read ?LicenceRequest $licenceRequest
 */
class Licence extends Model
{
    /** @use HasFactory<\Database\Factories\LicenceFactory> */
    use HasFactory;

    use HasLicenceType;
    use HasUlids;

    protected $attributes = [
        'includes_competitor' => false,
    ];

    protected $hidden = [
        'id',
        'number',
        'number_hash',
        'holder',
    ];

    protected $fillable = [
        'user_id',
        'provider',
        'licence_type',
        'includes_competitor',
        'role',
        'race_licence_category',
        'number',
        'validity_year',
        'holder',
        'source',
        'verified_at',
        'verified_by',
        'temporary_licence_path',
        'licence_request_id',
    ];

    /**
     * The hash race registration stores for the same licence number, see participants.driver_licence.
     */
    public static function hashNumber(string $number): string
    {
        return hash('sha512', $number);
    }

    /**
     * The Licence with the given ULID, null when none is given.
     */
    public static function findByUlid(?string $ulid): ?self
    {
        return $ulid === null ? null : self::query()->where('ulid', $ulid)->sole();
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
     * The User managing the licence.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The request the licence was emitted from.
     *
     * @return BelongsTo<LicenceRequest, $this>
     */
    public function licenceRequest(): BelongsTo
    {
        return $this->belongsTo(LicenceRequest::class);
    }

    /**
     * Whether the organizer can rely on the licence: emitted on the platform, or declared and verified.
     */
    public function isVerifiedOrEmitted(): bool
    {
        return $this->source === LicenceSource::Emitted || $this->isVerified();
    }

    /**
     * The organizer verified the declared licence.
     */
    public function markVerifiedBy(User $organizer): void
    {
        $this->update([
            'verified_at' => now(),
            'verified_by' => $organizer->getKey(),
        ]);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Whether it is a declared licence the organizer has not verified yet. Only then the managing User can change or remove it.
     */
    public function isAwaitingVerification(): bool
    {
        return $this->source === LicenceSource::Declared && ! $this->isVerified();
    }

    /**
     * A link to download the temporary licence, valid for 15 minutes. Null when no temporary licence is attached.
     */
    public function temporaryLicenceUrl(): ?string
    {
        if ($this->temporary_licence_path === null) {
            return null;
        }

        return URL::temporarySignedRoute('my-licences.temporary-licence', now()->addMinutes(15), ['licence' => $this]);
    }

    /**
     * Only the declared licences the organizer has not verified yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAwaitingVerification(Builder $query): void
    {
        $query->where('source', LicenceSource::Declared)->whereNull('verified_at');
    }

    /**
     * Only the driver licences that can be enrolled in the championship: valid in its year and, when it names a
     * provider, emitted by it.
     *
     * @param  Builder<self>  $query
     */
    public function scopeEnrollableIn(Builder $query, Championship $championship): void
    {
        $query->where('role', LicenceRole::Driver)
            ->where('validity_year', $championship->start_at->year)
            ->when($championship->licences->provider, fn (Builder $query, LicenceProvider $provider) => $query->where('provider', $provider));
    }

    /**
     * Only the licences that can name the Competitor in the championship: including a competitor licence, valid in its
     * year.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCompetitorIn(Builder $query, Championship $championship): void
    {
        $query->where('includes_competitor', true)
            ->where('validity_year', $championship->start_at->year);
    }

    /**
     * Only the licences managed by the given User.
     *
     * @param  Builder<self>  $query
     */
    public function scopeManagedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->getKey());
    }

    protected static function booted(): void
    {
        static::saving(function (Licence $licence) {
            if ($licence->isDirty('number')) {
                $licence->number_hash = self::hashNumber($licence->number);
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
            'role' => LicenceRole::class,
            'includes_competitor' => 'boolean',
            'race_licence_category' => DriverLicence::class,
            'number' => 'encrypted',
            'validity_year' => 'integer',
            'holder' => HolderData::class.':encrypted',
            'source' => LicenceSource::class,
            'verified_at' => 'datetime',
        ];
    }
}
