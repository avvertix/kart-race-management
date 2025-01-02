<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver',
        'bonus_type',
        'driver_licence',
        'driver_licence_hash',
        'contact_email',
        'amount',
    ];

    protected $withCount = [
        'usages',
    ];

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }

    public function usages()
    {
        return $this->belongsToMany(Participant::class, 'participant_bonus');
    }

    public function scopeLicenceHash($query, $licenceHash)
    {
        return $query->where('driver_licence_hash', $licenceHash);
    }

    public function scopeLicence($query, $licence)
    {
        return $query->where('driver_licence_hash', hash('sha512', $licence));
    }

    public function remaining(): int
    {
        return $this->amount - $this->usages()->count();
    }

    public function hasRemaining(): bool
    {
        return $this->remaining() > 0;
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'bonus_type' => BonusType::class,
            'driver_licence' => 'encrypted',
        ];
    }
}
