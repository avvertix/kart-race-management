<?php

namespace App\Models;


enum ResultStatus: int
{

    case FINISHED = 10;
    case DID_NOT_START = 20;
    case DID_NOT_FINISH = 30;
    case DISQUALIFIED = 40;


    public static function fromString(string $value): static
    {
        return match (strtolower($value)) {
            'dns' => static::DID_NOT_START,
            'dnf' => static::DID_NOT_FINISH,
            'dsq' => static::DISQUALIFIED,
            default => static::FINISHED,
        };
    }

    public static function matchUnfinishedOrPenalty(string $value): bool
    {
        return match (strtolower($value)) {
            'dns' => true,
            'dnf' => true,
            'dsq' => true,
            default => false,
        };
    }
}
