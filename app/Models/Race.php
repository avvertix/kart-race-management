<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'event_start_at',
        'event_end_at',
        'registration_opens_at',
        'registration_closes_at',
        'track',
        'title',
        'description',
        'tags',
        'properties',
        'hide',
        'participant_limits',
        'type',
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
        'event_start_at' => 'datetime',
        'event_end_at' => 'datetime',
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'canceled_at' => 'datetime',
        'tags' => AsCollection::class,
        'properties' => AsArrayObject::class,
        'hide' => 'boolean',
        'participant_limits' => AsCollection::class,
        'type' => RaceType::class,
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
     * Get the championship that contains the race.
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * Get the participants to the race.
     */
    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function getPeriodAttribute()
    {
        if($this->event_end_at && !$this->event_end_at->isSameDay($this->event_start_at)){
            return $this->event_start_at->locale(app()->currentLocale())->setTimezone($this->timezone)->isoFormat('l') . ' — ' . $this->event_end_at->locale(app()->currentLocale())->setTimezone($this->timezone)->isoFormat('l');
        }

        return $this->event_start_at->locale(app()->currentLocale())->setTimezone($this->timezone)->isoFormat('l');
    }

    /**
     * Is the registration open for the race?
     */
    public function getIsRegistrationOpenAttribute()
    {
        $now = now();

        return 
            $this->registration_opens_at->lessThanOrEqualTo($now) &&
            $this->registration_closes_at->greaterThanOrEqualTo($now);
    }

    public function getStatusAttribute()
    {
        if(!is_null($this->canceled_at)){
            return 'canceled';
        }

        $todayStartOfDay = today();
        $todayEndOfDay = today()->endOfDay();
        
        if($this->event_start_at->betweenIncluded($todayStartOfDay, $todayEndOfDay)
           || $this->event_end_at->betweenIncluded($todayStartOfDay, $todayEndOfDay)){
            return 'active';
        };

        if($this->isRegistrationOpen){
            return 'registration_open';
        }

        if($todayStartOfDay->lessThan($this->event_start_at)){
            return 'scheduled';
        };
        
        return 'concluded';
    }

    public function getTimezoneAttribute()
    {
        return config('races.timezone', 'UTC');
    }


    /**
     * Filter races available for registration
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRegistrationOpen($query)
    {
        $now = now();

        return $query
            ->where('registration_opens_at', '<=', $now)
            ->where('registration_closes_at', '>=', $now);
    }
    
    /**
     * Filter races that happens today
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where('registration_closes_at', '<=', $now)
            ->where('event_end_at', '>=', $now);
    }
    
    /**
     * Filter only visible races
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query
            ->where('hide', false);
    }
    
    /**
     * Filter races that are hidden
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHidden($query)
    {
        return $query
            ->where('hide', true);
    }

    public function hasTotalParticipantLimit()
    {
        $limit = $this->getTotalParticipantLimit();

        return $limit !== null;
    }

    public function getTotalParticipantLimit()
    {
        return $this->participant_limits?->get('total');
    }


    public function isZonal()
    {
        return $this->type == RaceType::ZONE;
    }

}
