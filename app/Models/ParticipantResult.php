<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantResult extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'run_result_id',
        'participant_id',
        'bib',
        'status',
        'name',
        'category',
        'position',
        'position_in_category',
        'gap_from_leader',
        'gap_from_previous',
        'best_lap_time',
        'best_lap_number',
        'racer_hash',
        'is_dnf',
        'is_dns',
        'is_dq',
        'points',
        'laps',
        'total_race_time',
        'second_best_time',
        'second_best_lap_number',
        'best_speed',
        'second_best_speed',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bib' => 'integer',
            'status' => ResultStatus::class,
            'is_dnf' => 'boolean',
            'is_dns' => 'boolean',
            'is_dq' => 'boolean',
            'points' => 'float',
            'laps' => 'integer',
            'best_speed' => 'float',
            'second_best_speed' => 'float',
        ];
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
     * Get the run result this participant result belongs to.
     */
    public function runResult(): BelongsTo
    {
        return $this->belongsTo(RunResult::class);
    }

    /**
     * Get the participant this result belongs to (nullable).
     * This relationship can be established via participant_id or racer_hash.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
