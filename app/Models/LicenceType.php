<?php

declare(strict_types=1);

namespace App\Models;

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
