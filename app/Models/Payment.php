<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Money received, or a proof of it. For a race participant: the proof uploaded with the registration. For an Enrollment
 * or a Licence Request: a payment record with the amount the organizer confirmed, the channel and its review, with a
 * proof when the driver uploaded one.
 *
 * @property int $id
 * @property ?int $participant_id
 * @property ?string $path
 * @property ?string $hash
 * @property ?int $amount
 * @property ?PaymentChannelType $channel
 * @property ?PaymentStatus $status
 * @property ?int $recorded_by
 * @property ?int $reviewed_by
 * @property ?CarbonImmutable $reviewed_at
 * @property CarbonImmutable $created_at
 * @property-read string $downloadUrl
 */
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
        'amount',
        'channel',
        'status',
        'recorded_by',
        'reviewed_by',
        'reviewed_at',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * What the payment is for: an Enrollment or a Licence Request. Race participant payments use participant instead.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<Model, $this>
     */
    public function payable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Whether a proof was uploaded: payments recorded by the organizer have none.
     */
    public function hasProof(): bool
    {
        return $this->path !== null;
    }

    /**
     * Mark a submitted proof as confirmed with the amount received, in cents, or as rejected when no amount is given.
     */
    public function review(User $organizer, ?int $amount): void
    {
        $this->update([
            'status' => $amount === null ? PaymentStatus::Rejected : PaymentStatus::Confirmed,
            'amount' => $amount,
            'reviewed_by' => $organizer->getKey(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Delete the record and, once the transaction commits, its file.
     */
    public function deleteWithFile(): void
    {
        $path = $this->path;

        $this->delete();

        if ($path !== null) {
            DB::afterCommit(fn () => Storage::disk('payments')->delete($path));
        }
    }

    /**
     * Only the payment records the organizer confirmed.
     *
     * @param  Builder<self>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', PaymentStatus::Confirmed);
    }

    /**
     * Only the proofs waiting for the organizer.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSubmitted(Builder $query): void
    {
        $query->where('status', PaymentStatus::Submitted);
    }

    protected function downloadUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            return URL::temporarySignedRoute('payment-verification.show', 15 * Carbon::SECONDS_PER_MINUTE, ['payment' => $this->getKey()]);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'channel' => PaymentChannelType::class,
            'status' => PaymentStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
