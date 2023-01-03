<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mechanic extends Model
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'licence_renewed_at' => 'datetime',
        'licence_type' => LicenceType::class,
        'licence_number' => 'encrypted',
        'nationality' => 'encrypted',
        'name' => 'encrypted',
        'surnname' => 'encrypted',
    ];

    /**
     * Get the participant.
     */
    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

}
