<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * @property string $name
 * @property string $price
 */
class TireOption extends Fluent
{
    protected static $types = null;

    /**
     * Get all defined tire options
     *
     * @deprecated Use \App\Models\ChampionshipTire
     */
    public static function allTires(): Collection
    {
        self::$types = collect(config('races.tires'))
            ->mapInto(self::class);

        return self::$types;
    }

    /**
     * Get a tire option by its key
     *
     * @deprecated Use \App\Models\ChampionshipTire
     */
    public static function find($key): ?self
    {
        return self::all()->get($key);
    }

    /**
     * @deprecated Use \App\Models\ChampionshipTire
     */
    public static function search($term): Collection
    {
        return self::all()->filter(function ($value, $key) use ($term) {
            return Str::contains($value->get('name'), $term, true);
        });
    }
}
