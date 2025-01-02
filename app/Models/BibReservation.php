<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibReservation extends Model
{
    use HasFactory;

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
    ];

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
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
        return $query->where(function($subQuery){
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
        if(!$this->reservation_expires_at){
            return false;
        }

        return $this->reservation_expires_at->lessThanOrEqualTo(now());
    }

    public function isEnforcedUsingLicence(): bool
    {
        return !is_null($this->driver_licence_hash);
    }

    public function isReservedToLicenceHash($hash): bool
    {
        if(!$this->driver_licence_hash){
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
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'licence_type' => DriverLicence::class,
            'driver_licence' => 'encrypted',
            'reservation_expires_at' => 'datetime',
        ];
    }

}
