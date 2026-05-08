<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampionshipPenalty extends Model
{
    use HasFactory;
    use HasUlids;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'championship_id',
        'title',
        'description',
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

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }
}
