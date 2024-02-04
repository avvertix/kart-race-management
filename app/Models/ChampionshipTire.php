<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ChampionshipTire extends Model
{
    use HasFactory;

    use HasUlids;

    use LogsActivity;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'championship_id',
    ];

    protected $casts = [
        'price' => 'int',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['ulid'];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }
    
    /**
     * The category that uses the tire
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
    
    public function participants()
    {
        return $this->hasManyThrough(Participant::class, Category::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'price',
            ])
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function formattedPrice(): string
    {
        return Number::currency($this->price / 100, in: 'EUR');
    }
}
