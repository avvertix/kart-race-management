<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'path',
        'hash',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    protected function downloadUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            return URL::temporarySignedRoute('payment-verification.show', 15 * Carbon::SECONDS_PER_MINUTE, ['payment' => $this->getKey()]);
        });
    }
}
