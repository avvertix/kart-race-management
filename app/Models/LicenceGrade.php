<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A common grade of a Licence Type, e.g. C Junior. ACI grades belong to a level.
 *
 * @property int $licence_type_id
 * @property ?LicenceLevel $level
 * @property string $name
 * @property int $sort
 */
class LicenceGrade extends Model
{
    /** @use HasFactory<\Database\Factories\LicenceGradeFactory> */
    use HasFactory;

    protected $fillable = [
        'level',
        'name',
        'sort',
    ];

    /**
     * @return BelongsTo<LicenceType, $this>
     */
    public function licenceType(): BelongsTo
    {
        return $this->belongsTo(LicenceType::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => LicenceLevel::class,
        ];
    }
}
