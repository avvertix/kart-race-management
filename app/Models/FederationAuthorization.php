<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * Authorization issued by the driver's national sporting federation
 * that allows a foreign licence holder to participate in a race.
 */
class FederationAuthorization extends Model
{
    /** @use HasFactory<\Database\Factories\FederationAuthorizationFactory> */
    use HasFactory;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'path',
        'hash',
    ];

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::make(get: function () {
            return URL::temporarySignedRoute('federation-authorization.show', 15 * Carbon::SECONDS_PER_MINUTE, ['authorization' => $this->getKey()]);
        });
    }
}
