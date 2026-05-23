<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItalianRegion;
use Illuminate\Database\Eloquent\Model;

class ItalianPostalCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cap',
        'province_code',
        'province',
        'municipality',
        'region',
    ];

    public static function findRegionByCap(string $cap): ?ItalianRegion
    {
        return static::where('cap', $cap)->first()?->region;
    }

    protected function casts(): array
    {
        return [
            'region' => ItalianRegion::class,
        ];
    }
}
