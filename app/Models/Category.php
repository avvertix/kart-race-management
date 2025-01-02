<?php

namespace App\Models;

use App\Categories\Category as CategoryConfiguration;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Category extends Model
{
    use HasFactory;

    use HasUlids;

    use LogsActivity;

    protected $hidden = [
        'id',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'tire'
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'enabled',
        'short_name',
        'championship_tire_id',
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
     * Get the selectable tire for the category
     */
    public function tire()
    {
        return $this->hasOne(ChampionshipTire::class, 'id', 'championship_tire_id');
    }


    /**
     * Filter for enabled categories
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Filter for disabled categories
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }

    /**
     * Filter for enabled categories
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('enabled', true)
            ->where(function($subQuery) use ($term){ 
                return $subQuery->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }


    public function asCategoryConfiguration(): CategoryConfiguration
    {
        return new CategoryConfiguration([
            'name' => $this->name,
            'description' => $this->description,
            'tires' => $this->tire?->code,
            'tire_name' => $this->tire?->name,
            'tire_price' => $this->tire?->price,
            'timekeeper_label' => $this->short_name ?? $this->name,
            'enabled' => $this->enabled,
        ]);
    }
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }
}
