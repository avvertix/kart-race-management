<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

class RaceCommunication extends Model
{
    use HasFactory;
    use HasUlids;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'race_id',
        'championship_id',
        'user_id',
        'type',
        'run_type',
        'message',
        'read_at',
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

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    protected function casts(): array
    {
        return [
            'type' => CommunicationType::class,
            'run_type' => RunType::class,
            'read_at' => 'datetime',
        ];
    }
}
