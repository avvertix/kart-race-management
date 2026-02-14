<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampionshipPointScheme extends Model
{
    use HasFactory;
    use HasUlids;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'name',
        'points_config',
        'championship_id',
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

    /**
     * Get the championship.
     */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * Get the points awarded for a given position in a run type.
     */
    public function getPointsForPosition(RunType $runType, int $position): float
    {
        $config = $this->points_config[$runType->value] ?? null;

        if (! $config || $position < 1) {
            return 0;
        }

        $positions = $config['positions'] ?? [];

        return (float) ($positions[$position - 1] ?? 0);
    }

    /**
     * Get the points awarded for a non-finished status in a run type.
     */
    public function getPointsForStatus(RunType $runType, ResultStatus $status): float
    {
        $config = $this->points_config[$runType->value] ?? null;

        if (! $config) {
            return 0;
        }

        $statuses = $config['statuses'] ?? [];

        return (float) ($statuses[$status->value] ?? 0);
    }

    protected function casts(): array
    {
        return [
            'points_config' => AsArrayObject::class,
        ];
    }
}
