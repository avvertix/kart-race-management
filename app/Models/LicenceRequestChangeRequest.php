<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceRequestSection;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A section of a Licence Request the organizer asked to change, with a note shown to the requester.
 *
 * @property int $licence_request_id
 * @property LicenceRequestSection $section
 * @property string $note
 * @property ?CarbonImmutable $resolved_at
 * @property-read LicenceRequest $licenceRequest
 */
class LicenceRequestChangeRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LicenceRequestChangeRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'section',
        'note',
        'resolved_at',
    ];

    /**
     * @return BelongsTo<LicenceRequest, $this>
     */
    public function licenceRequest(): BelongsTo
    {
        return $this->belongsTo(LicenceRequest::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => LicenceRequestSection::class,
            'resolved_at' => 'datetime',
        ];
    }
}
