<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceLevel;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A licence an organizer processes, e.g. the ACI driver licence, with its grades and required documents.
 *
 * @property LicenceProvider $provider
 * @property LicenceRole $role
 * @property string $name
 * @property ?string $description
 * @property ?DriverLicence $race_licence_category
 * @property bool $active
 */
class LicenceType extends Model
{
    /** @use HasFactory<\Database\Factories\LicenceTypeFactory> */
    use HasFactory;

    use HasUlids;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'provider',
        'role',
        'name',
        'description',
        'race_licence_category',
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
     * The common grades applicants can pick from.
     *
     * @return HasMany<LicenceGrade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(LicenceGrade::class)->orderBy('sort');
    }

    /**
     * The documents and forms a request for this licence needs.
     *
     * @return HasMany<LicenceTypeDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(LicenceTypeDocument::class)->orderBy('sort');
    }

    /**
     * Only the types offered for new requests.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * The types a Licence can be declared for: the active ones, plus the type the given Licence already has.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOfferedFor(Builder $query, ?Licence $licence = null): void
    {
        $query->where(fn (Builder $query) => $query->where('active', true)->orWhere('id', $licence->licence_type_id ?? 0));
    }

    /**
     * The licence category a driver licence of this type counts as in race registration: from the level for ACI, configured on the type for ASI. Null for other roles.
     */
    public function raceLicenceCategory(?LicenceLevel $level): ?DriverLicence
    {
        if ($this->role !== LicenceRole::Driver) {
            return null;
        }

        return $level?->raceLicence() ?? $this->race_licence_category;
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
            'race_licence_category' => DriverLicence::class,
            'active' => 'boolean',
        ];
    }
}
