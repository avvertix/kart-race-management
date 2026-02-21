<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\PointsConfigCast;
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
        return $this->points_config->getPointsForPosition($runType, $position);
    }

    /**
     * Get the points awarded for a non-finished status in a run type.
     *
     * When mode is "ranked", uses position-based points.
     * When mode is "fixed", returns the configured point value.
     */
    public function getPointsForStatus(RunType $runType, ResultStatus $status, ?int $position = null): float
    {
        return $this->points_config->getPointsForStatus($runType, $status, $position);
    }

    /**
     * Check if a status is configured to use position-based (ranked) points.
     */
    public function isStatusRanked(RunType $runType, ResultStatus $status): bool
    {
        return $this->points_config->isStatusRanked($runType, $status);
    }

    protected function casts(): array
    {
        return [
            'points_config' => PointsConfigCast::class,
        ];
    }
}
