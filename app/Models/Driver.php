<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\AddressData;
use App\Data\BirthData;
use App\Data\LicenceData;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'code',
        'email',
        'phone',
        'first_name',
        'last_name',
        'fiscal_code',
        'licence_number',
        'licence_hash',
        'licence_type',
        'user_id',
        'championship_id',
        'birth_date_hash',
        'birth',
        'address',
        'medical_certificate_expiration_date',
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

        'birth' => BirthData::class,
        'address' => AddressData::class,

        'medical_certificate_expiration_date' => 'date',
    ];

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function licence(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => LicenceData::from([
                'number' => $attributes['licence_number'],
                'type' => $attributes['licence_type'],
            ]
            ),
            set: fn (LicenceData $value) => [
                'licence_number' => $value->number,
                'licence_hash' => $value->hash(),
                'licence_type' => $value->type,
            ],
        );
    }
}
