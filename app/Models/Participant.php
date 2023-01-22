<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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
    
    

    public function qrCodeSvg()
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($this->qrCodeContent());

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    public function qrCodeContent(): string
    {
        return base64_encode("{$this->id}/{$this->uuid}");
    }
}
