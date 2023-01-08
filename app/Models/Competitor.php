<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competitor extends Model
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
        'residence_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'licence_type' => CompetitorLicence::class,
        'licence_renewed_at' => 'datetime',
        'licence_number' => 'encrypted',
        'name' => 'encrypted',
        'surname' => 'encrypted',
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'birth_date' => 'encrypted',
        'birth_place' => 'encrypted',
        'residence_address' => 'encrypted',
        'medical_certificate_expiration_date' => 'encrypted',
        'sex' => 'encrypted',
    ];

    
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }
}
