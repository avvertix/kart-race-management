<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\AliasesData;
use App\Data\RegistrationCostData;
use App\Notifications\ConfirmParticipantRegistration;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Participant extends Model implements HasLocalePreference
{
    use HasFactory;
    use HasUlids;
    use LogsActivity;
    use Notifiable;

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
        'racer_hash',
        'licence_type',
        'competitor_licence',
        'driver',
        'competitor',
        'mechanic',
        'vehicles',
        'use_bonus',
        'locale',
        'registration_completed_at',
        'payment_channel',
        'notes',
        'aliases',
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
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'racingCategory',
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
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Get the signatures applied to the participation
     */
    public function signatures()
    {
        return $this->hasMany(Signature::class);
    }

    /**
     * Get the payment proofs linked to the participation
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the tires assigned to the participant
     */
    public function tires()
    {
        return $this->hasMany(Tire::class);
    }

    /**
     * Get the transponders assigned to the participant
     */
    public function transponders()
    {
        return $this->hasMany(Transponder::class);
    }

    public function participationHistory()
    {
        return $this
            ->hasMany(self::class, 'driver_licence', 'driver_licence')
            ->orderBy('created_at');
    }

    public function bonuses()
    {
        return $this->belongsToMany(Bonus::class, 'participant_bonus')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function reservations()
    {
        return $this->hasMany(BibReservation::class, 'bib', 'bib');
    }

    /**
     * Get the participant results for this participant.
     * Results are linked via participant_id when available.
     */
    public function results()
    {
        return $this->hasMany(ParticipantResult::class);
    }

    /**
     * Filter races available for registration
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $terms)
    {
        return $query->when($terms, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('bib', e($search))
                    ->orWhere('first_name', 'LIKE', e($search).'%')
                    ->orWhere('last_name', 'LIKE', e($search).'%')
                    ->orWhere('driver_licence', hash('sha512', $search))
                    ->orWhere('competitor_licence', hash('sha512', $search));
            });
        });
    }

    public function scopeLicenceHash($query, $licence)
    {
        return $query->where('driver_licence', $licence);
    }

    public function scopeLicence($query, $licence)
    {
        return $query->where('driver_licence', hash('sha512', $licence));
    }

    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmed_at');
    }

    public function qrCodeSvg()
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($this->qrCodeUrl());

        return mb_trim(mb_substr($svg, mb_strpos($svg, "\n") + 1));
    }

    public function tiresQrCodeSvg()
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($this->tiresQrCodeUrl());

        return mb_trim(mb_substr($svg, mb_strpos($svg, "\n") + 1));
    }

    public function signedUrlParameters(): array
    {
        return ['registration' => $this, 'p' => $this->signatureContent()];
    }

    public function qrCodeUrl(): string
    {
        return URL::signedRoute(
            'registration.show',
            $this->signedUrlParameters()
        );
    }

    public function tiresQrCodeUrl(): string
    {
        return URL::signedRoute(
            'tires-verification.show',
            ['registration' => $this, 'p' => md5((string) $this->uuid)]
        );
    }

    public function signatureContent(): string
    {
        return "{$this->bib}-{$this->driver_licence}";
    }

    /**
     * Determine if the participant has signed the participation request.
     *
     * @return bool
     */
    public function hasSignedTheRequest()
    {
        return $this->signatures()->count() > 0;
    }

    /**
     * Send the participant verification notification.
     *
     * @return void
     */
    public function sendConfirmParticipantNotification()
    {
        $this->notify(new ConfirmParticipantRegistration);

        if ($this->competitor['email'] ?? false) {
            if ($this->competitor['email'] !== $this->driver['email']) {
                $this->notify(new ConfirmParticipantRegistration('competitor'));
            }
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

        if ($notification instanceof ConfirmParticipantRegistration && $notification->target === 'competitor' && $this->competitor) {
            return [
                // $this->driver['email'] => "{$this->first_name} {$this->last_name}",
                $this->competitor['email'] => "{$this->competitor['first_name']} {$this->competitor['last_name']}",
            ];
        }

        return [
            $this->driver['email'] => "{$this->first_name} {$this->last_name}",
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
            Carbon::now()->addMinutes(Config::get('participant.verification.expire', 12 * Carbon::MINUTES_PER_HOUR)),
            [
                'p' => (string) $this->uuid,
                't' => $target,
                'hash' => sha1($this->getEmailForVerification($target)),
            ]
        );
    }

    /**
     * Get the price details for the participant.
     */
    public function price(): Collection
    {
        // Use cost field if available, otherwise calculate the cost

        if (filled($this->cost)) {
            return $this->cost->details();
        }

        return $this->calculateParticipationCost()->details();
    }

    public function calculateParticipationCost(): RegistrationCostData
    {
        // Use category-specific registration price if available, otherwise fall back to championship or config default
        $raceFee = $this->racingCategory->registration_price
            ?? $this->championship->registration_price
            ?? (int) config('races.price');

        // Sum the actual deducted amounts from the pivot table
        $totalBonusDiscount = $this->use_bonus
            ? $this->bonuses->sum('pivot.amount')
            : 0;

        $tire = $this->racingCategory->tire;

        return new RegistrationCostData(
            registration_cost: $raceFee,
            tire_cost: $tire?->price,
            tire_model: $tire?->name,
            discount: $totalBonusDiscount > 0 ? 0 - $totalBonusDiscount : 0,
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'bib',
                'category',
                'category_id',
                'first_name',
                'last_name',
                'added_by',
                'confirmed_at',
                'driver_licence',
                'licence_type',
                'competitor_licence',
                'vehicles',
                'use_bonus',
                'driver->email',
                'competitor->email',
                'cost',
            ])
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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

    public function markOutOfZone($outOfZone = true)
    {
        $this->properties['out_of_zone'] = $outOfZone;
        $this->save();
    }

    public function wasProcessedForOutOfZone()
    {
        if (is_null($this->properties['out_of_zone'] ?? null)) {
            return false;
        }

        return true;
    }

    public function outOfZoneStatus()
    {
        if (! $this->wasProcessedForOutOfZone()) {
            return __('Out of zone not yet evaluated');
        }

        return $this->properties['out_of_zone'] ? __('Out of zone') : __('Within zone');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Participant $participant) {
            // Auto-populate racer_hash from driver_licence if it's not already set
            if ($participant->driver_licence && empty($participant->racer_hash)) {
                $participant->racer_hash = mb_substr($participant->driver_licence, 0, 8);
            }
        });
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

    protected function competitorEmail(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            return $this->competitor['email'] ?? null;
        });
    }

    protected function fullName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            return str()->title($this->first_name.' '.$this->last_name);
        });
    }

    /**
     * The attributes that should be cast.
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
            'properties' => AsArrayObject::class,
            'wildcard' => 'boolean',
            'payment_channel' => PaymentChannelType::class,
            'aliases' => AliasesData::class,
            'cost' => RegistrationCostData::class,

        ];
    }
}
