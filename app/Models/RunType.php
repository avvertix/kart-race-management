<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Support\Str;

enum RunType: int
{

    case WARM_UP = 10;
    case QUALIFY = 20;
    case RACE_1 = 30;
    case RACE_2 = 40;

    public static function fromString(?string $value): static
    {
        if(blank($value)){
            throw new InvalidArgumentException("Cannot identify run from an empty text.");
        }

        if(Str::contains($value, ['gara 1', 'race 1', 'gara1', 'gara-1', 'race1', 'race-1', 'prefinale', 'pre-finale'], ignoreCase: true)){
            return static::RACE_1;
        }

        if(Str::contains($value, ['gara 2', 'race 2', 'gara2', 'race2', 'gara-2', 'race-2', 'finale', 'final'], ignoreCase: true)){
            return static::RACE_2;
        }

        if(Str::contains($value, ['qualifiche', 'qualifying', 'prove cronometrate', 'cronometrate'], ignoreCase: true)){
            return static::QUALIFY;
        }

        if(Str::contains($value, ['prove libere', 'libere', 'warm up', 'warmup', 'warm-up', 'practice'], ignoreCase: true)){
            return static::WARM_UP;
        }

        throw new InvalidArgumentException("Cannot identify run from [{$value}].");
    }

    public function isPractice(): bool
    {
        return $this === static::WARM_UP;
    }

    public function isQualify(): bool
    {
        return $this === static::QUALIFY;
    }

    public function isRace(): bool
    {
        return in_array($this, [static::RACE_1, static::RACE_2]);
    }
}
