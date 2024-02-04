<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'bonus_type' => BonusType::class,
        'driver_licence' => 'encrypted',
    ];

    protected $fillable = [
        'driver',
        'bonus_type',
        'driver_licence',
        'driver_licence_hash',
        'contact_email',
        'amount',
    ];

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }
}
