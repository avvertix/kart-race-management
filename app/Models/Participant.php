<?php

namespace App\Models;

use App\Notifications\ConfirmParticipantRegistration;
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
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class Participant extends Model
{
    use HasFactory;
    
    use HasUlids;

    use Notifiable;

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

    /**
     * Get the signatures applied to the participation
     */
    public function signatures()
    {
        return $this->hasMany(Signature::class);
    }


    public function getEngineAttribute($value = null)
    {
        return $this->vehicles[0]['engine_manufacturer'] ?? null;
    }
    
    public function getEmailAttribute($value = null)
    {
        return $this->driver['email'] ?? null;
    }
    
    

    public function qrCodeSvg()
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($this->qrCodeUrl());

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    public function qrCodeUrl(): string
    {
        return URL::signedRoute(
            'registration.show',
            ['registration' => $this, 'p' => $this->signatureContent()]
        );
    }
    
    public function signatureContent(): string
    {
        return "{$this->bib}-{$this->driver_licence}";
    }

    /**
     * Send the participant verification notification.
     *
     * @return void
     */
    public function sendConfirmParticipantNotification()
    {
        $this->notify(new ConfirmParticipantRegistration);
        
        if($this->competitor['email'] ?? false){
            
            $this->notify(new ConfirmParticipantRegistration('competitor'));
        }
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification)
    {
        // returning multiple values result in a single notification with more recipients
        // since we want a specific notification to verify the email addresses
        // we need to enqueue it twice and have identifiable recipients
        
        if($notification instanceof ConfirmParticipantRegistration && $notification->target === 'competitor' && $this->competitor){
            return [
                // $this->driver['email'] => "{$this->first_name} {$this->last_name}",
                $this->competitor['email'] => "{$this->competitor['first_name']} {$this->competitor['last_name']}"
            ];
        }
        
        return [
            $this->driver['email'] => "{$this->first_name} {$this->last_name}"
        ];
    }

    /**
     * Get the email to verify according to the target of the notification
     */
    public function getEmailForVerification($target = 'driver')
    {
        return $target === 'competitor' && $this->competitor ? $this->competitor['email'] : $this->driver['email'];
    }

    /**
     * Get the verification URL for the given notification target.
     *
     * @param  string  $target
     * @return string
     */
    public function verificationUrl($target = 'driver')
    {
        return URL::temporarySignedRoute(
            'participant.sign.create',
            Carbon::now()->addMinutes(Config::get('participant.verification.expire', 60)),
            [
                'p' => (string)$this->uuid,
                't' => $target,
                'hash' => sha1($this->getEmailForVerification($target)),
            ]
        );
    }
}
