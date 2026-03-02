<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChampionshipAward extends Model
{
    use HasFactory;
    use HasUlids;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'championship_id',
        'name',
        'type',
        'ranking_mode',
        'best_n',
        'wildcard_filter',
        'category_id',
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
     * Get the category (for category awards).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the selected categories (for overall awards).
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'championship_award_category');
    }

    /**
     * Get the selected races (for specific-races mode).
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(Race::class, 'championship_award_race');
    }

    public function isCategoryAward(): bool
    {
        return $this->type === AwardType::Category;
    }

    public function isOverallAward(): bool
    {
        return $this->type === AwardType::Overall;
    }

    protected function casts(): array
    {
        return [
            'type' => AwardType::class,
            'ranking_mode' => AwardRankingMode::class,
            'wildcard_filter' => WildcardFilter::class,
            'best_n' => 'integer',
        ];
    }
}
