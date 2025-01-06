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

    public static function fromString(string $value): static
    {
        if(Str::contains($value, ['gara 1', 'race 1'], ignoreCase: true)){
            return static::RACE_1;
        }
        
        if(Str::contains($value, ['gara 2', 'race 2'], ignoreCase: true)){
            return static::RACE_2;
        }
        
        if(Str::contains($value, ['prove cronometrate', 'cronometrate'], ignoreCase: true)){
            return static::QUALIFY;
        }
        
        if(Str::contains($value, ['prove libere', 'libere'], ignoreCase: true)){
            return static::WARM_UP;
        }
        
        throw new InvalidArgumentException("Cannot identify the run from the [{$value}]");
    }
}
