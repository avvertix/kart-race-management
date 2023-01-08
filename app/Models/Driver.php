<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'licence_type',
        'licence_number',
        'licence_renewed_at',
        'nationality',
        'name',
        'surname',
        'email',
        'phone',
        'birth_date',
        'birth_place',
        'medical_certificate_expiration_date',
        'residence_address',
        'sex',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'licence_renewed_at' => 'datetime',
        'licence_type' => DriverLicence::class,
        'sex' => Sex::class,
        'licence_number' => 'encrypted',
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'nationality' => 'encrypted',
        'birth_date' => 'encrypted',
        'birth_place' => 'encrypted',
        'residence_address' => 'encrypted',
        'medical_certificate_expiration_date' => 'encrypted',
    ];

    /**
     * Get the participant.
     */
    public function participants()
    {
        return $this->belongsTo(Participant::class);
    }

    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }
}
