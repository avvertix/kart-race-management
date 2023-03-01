<?php

namespace App\Models;

use App\Categories\Category;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrashedParticipant extends Model
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
        'first_name',
        'last_name',
        'added_by',
        'confirmed_at',
        'consents',
        'race_id',
        'championship_id',
        'driver_licence',
        'licence_type',
        'competitor_licence',
        'driver',
        'competitor',
        'mechanic',
        'vehicles',
        'use_bonus',
        'locale',
        'registration_completed_at',
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
        'licence_type' => DriverLicence::class,
        'driver' => 'encrypted:json',
        'competitor' => 'encrypted:json',
        'mechanic' => 'encrypted:json',
        'vehicles' => AsCollection::class,
        'confirmed_at' => 'datetime',
        'registration_completed_at' => 'datetime',
        'consents' => AsArrayObject::class,
        'use_bonus' => 'boolean',
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
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * Get the race
     */
    public function race()
    {
        return $this->belongsTo(Race::class);
    }


    public function getEngineAttribute($value = null)
    {
        return $this->vehicles[0]['engine_manufacturer'] ?? null;
    }
    
    public function getEmailAttribute($value = null)
    {
        return $this->driver['email'] ?? null;
    }
    
    public function category(): Category|null
    {
        return Category::find($this->category);
    }
    
    public function tire(): TireOption|null
    {
        return optional($this->category())->tire();
    }
    


    /**
     * Get the participant's preferred locale.
     *
     * @return string
     */
    public function preferredLocale()
    {
        return $this->locale ?? config('app.locale');
    }
}
