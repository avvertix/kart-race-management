<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RunResult extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'race_id',
        'run_type',
        'promoted_to',
        'title',
        'file_name',
        'published_at',
        'is_generated',
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
     * Get the race.
     */
    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Get the participant results for this run.
     */
    public function participantResults()
    {
        return $this->hasMany(ParticipantResult::class);
    }

    /**
     * Determine if the result is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->canBePublished();
    }

    /**
     * Series and heats are working sessions, only their combined classification is published.
     * A heat promoted to pre-final or final is published as that race.
     */
    public function canBePublished(): bool
    {
        return $this->isScored();
    }

    /**
     * The run type the result is scored, published and counted in awards as.
     */
    public function scoringRunType(): RunType
    {
        return $this->promoted_to ?? $this->run_type;
    }

    /**
     * Determine if the result receives points: working sessions do not, unless promoted.
     */
    public function isScored(): bool
    {
        return ! $this->scoringRunType()->isWorkingSession();
    }

    /**
     * Determine if the heat is promoted to pre-final or final.
     */
    public function isPromoted(): bool
    {
        return $this->promoted_to !== null;
    }

    /**
     * Scope a query to the results that can be published: not working sessions, or promoted ones.
     *
     * @param  Builder<RunResult>  $query
     * @return Builder<RunResult>
     */
    public function scopePublishable(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereNotIn('run_type', RunType::workingSessionValues())
            ->orWhereNotNull('promoted_to'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'run_type' => RunType::class,
            'promoted_to' => RunType::class,
            'published_at' => 'datetime',
            'is_generated' => 'boolean',
        ];
    }
}
