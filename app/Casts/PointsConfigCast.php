<?php

declare(strict_types=1);

namespace App\Casts;

use App\Data\PointsConfigData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<PointsConfigData, PointsConfigData|array>
 */
class PointsConfigCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): PointsConfigData
    {
        if ($value === null) {
            return new PointsConfigData;
        }

        $data = json_decode($value, true);

        return PointsConfigData::fromConfig($data);
    }

    /**
     * @param  PointsConfigData|array<mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PointsConfigData) {
            return json_encode($value->toConfig());
        }

        if (is_array($value)) {
            return json_encode(PointsConfigData::fromConfig($value)->toConfig());
        }

        return null;
    }
}
