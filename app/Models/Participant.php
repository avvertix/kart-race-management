<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;
    
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'bib',
        'category',
        'name',
        'surname',
        'added_by',
        'confirmed_at',
        'consents',
        'race_id',
        'championship_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'confirmed_at' => 'datetime',
        'consents' => AsArrayObject::class,
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }

    /**
     * Get the drivers details.
     */
    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Get the competitors details.
     */
    public function competitors()
    {
        return $this->hasMany(Competitor::class);
    }
    
    /**
     * Get the vehicles details.
     */
    public function mechanics()
    {
        return $this->hasMany(Mechanic::class);
    }

    /**
     * Get the vehicles details.
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class)->orderBy('created_at');
    }

    /**
     * Get the first driver details.
     */
    public function driver()
    {
        return $this->hasOne(Driver::class)->oldestOfMany();
    }

    /**
     * Get the first competitor details.
     */
    public function competitor()
    {
        return $this->hasOne(Competitor::class)->oldestOfMany();
    }
    
    /**
     * Get the first mechanic details.
     */
    public function mechanic()
    {
        return $this->hasOne(Mechanic::class)->oldestOfMany();
    }
    
    /**
     * Get the first vehicle details.
     */
    public function vehicle()
    {
        return $this->hasOne(Vehicle::class)->oldestOfMany();
    }
    
    
}
