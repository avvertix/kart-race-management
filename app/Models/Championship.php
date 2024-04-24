<?php

namespace App\Models;

use App\Data\WildcardSettingsData;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Championship extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'start_at',
        'end_at',
        'title',
        'description',
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
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'wildcard' => WildcardSettingsData::class . ':default',
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


    public function getPeriodAttribute()
    {
        return $this->start_at->toDateString() . ' — ' . (optional($this->end_at)->toDateString() ?? '...');
    }


    /**
     * Get the races within the championship.
     */
    public function races()
    {
        return $this->hasMany(Race::class)->orderBy('event_start_at');
    }
    
    /**
     * Get the categories that can participate.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
    
    /**
     * Get the allowed tire models.
     */
    public function tires()
    {
        return $this->hasMany(ChampionshipTire::class);
    }
    
    /**
     * Get the BIB reservations.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(BibReservation::class)->orderBy('bib', 'ASC');
    }
    
    /**
     * Get the bonus for drivers in this championship.
     */
    public function bonuses(): HasMany
    {
        return $this->hasMany(Bonus::class);
    }

}
