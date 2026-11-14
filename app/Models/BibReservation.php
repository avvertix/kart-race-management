<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BibReservationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * @property int $bib
 * @property string $driver
 * @property ?string $driver_licence
 * @property ?string $driver_licence_hash
 * @property ?string $contact_email
 * @property ?DriverLicence $licence_type
 * @property ?CarbonImmutable $reservation_expires_at
 * @property ?int $created_by_user_id
 * @property BibReservationStatus $status
 * @property ?CarbonImmutable $released_at
 */
class BibReservation extends Model
{
    use HasFactory;

    /**
     * The lowest race number that can be reserved.
     */
    public const MIN_BIB = 1;

    /**
     * The highest race number that can be reserved.
     */
    public const MAX_BIB = 999;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'bib',
        'driver_licence_hash',
        'driver_licence',
        'driver',
        'contact_email',
        'licence_type',
        'reservation_expires_at',
        'status',
        'created_by_user_id',
        'enrollment_id',
        'licence_id',
        'released_at',
    ];

    /**
     * A rule requiring the value of the column to be unique among the reservations of the championship that hold their number.
     */
    public static function uniqueAmongHolding(Championship $championship, string $column): Unique
    {
        return Rule::unique((new self())->getTable(), $column)
            ->where('championship_id', $championship->getKey())
            ->whereNot('status', BibReservationStatus::Released->value);
    }

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * The Enrollment that picked the number, if any.
     *
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * The user who created the reservation.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * The reservations that still hold their number: pending and confirmed ones.
     *
     * @param  Builder<BibReservation>  $query
     */
    public function scopeNotReleased(Builder $query): void
    {
        $query->where('status', '!=', BibReservationStatus::Released->value);
    }

    public function scopeInChamphionship($query, Championship|int $championship)
    {
        return $query->where('championship_id', is_int($championship) ? $championship : $championship->getKey());
    }

    public function scopeLicenceHash($query, $licence)
    {
        return $query->where('driver_licence_hash', $licence);
    }

    public function scopeLicence($query, $licence)
    {
        return $query->licenceHash(hash('sha512', $licence));
    }

    public function scopeRaceNumber($query, $bib)
    {
        return $query->where('bib', $bib);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($subQuery) {
            return $subQuery->whereNull('reservation_expires_at')
                ->orWhere('reservation_expires_at', '>', now());
        });
    }

    public function scopeWithoutLicence($query)
    {
        return $query->whereNull('driver_licence_hash');
    }

    public function isExpired(): bool
    {
        if (! $this->reservation_expires_at) {
            return false;
        }

        return $this->reservation_expires_at->lessThanOrEqualTo(now());
    }

    public function isEnforcedUsingLicence(): bool
    {
        return ! is_null($this->driver_licence_hash);
    }

    public function isReservedToLicenceHash($hash): bool
    {
        if (! $this->driver_licence_hash) {
            return false;
        }

        return $this->driver_licence_hash === $hash;
    }

    public function isReservedToDriver(string|array $driver): bool
    {

        $reservationDriver = str($this->driver)->split('/[\s,]+/');

        $requestedDriver = is_string($driver) ? str($this->driver)->split('/[\s,]+/') : collect($driver);

        return $reservationDriver->diff($requestedDriver)->isEmpty();
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'licence_type' => DriverLicence::class,
            'driver_licence' => 'encrypted',
            'reservation_expires_at' => 'datetime',
            'status' => BibReservationStatus::class,
            'released_at' => 'datetime',
        ];
    }
}
