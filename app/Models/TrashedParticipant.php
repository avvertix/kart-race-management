<?php

namespace App\Models;

use App\Categories\Category;
use App\Models\Category as ModelsCategory;
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
        'category_id',
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

    /**
     * Category
     */
    public function racingCategory()
    {
        return $this->belongsTo(ModelsCategory::class, 'category_id', 'id');
    }


    protected function engine(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            return $this->vehicles[0]['engine_manufacturer'] ?? null;
        });
    }
    
    protected function email(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            return $this->driver['email'] ?? null;
        });
    }
    
    /**
     * @deprecated
     */
    public function categoryConfiguration(): Category|null
    {
        return Category::find($this->category);
    }
    
    /**
     * @deprecated
     */
    public function tireConfiguration(): TireOption|null
    {
        return optional($this->categoryConfiguration())->tire();
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
    /**
     * The attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
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
    }
}
