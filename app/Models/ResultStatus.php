<?php

declare(strict_types=1);

namespace App\Models;

enum ResultStatus: int
{
    case FINISHED = 10;
    case DID_NOT_START = 20;
    case DID_NOT_FINISH = 30;
    case DISQUALIFIED = 40;

    public static function fromString(string $value): static
    {
        return match (mb_strtolower($value)) {
            'dns' => self::DID_NOT_START,
            'dnf' => self::DID_NOT_FINISH,
            'dsq' => self::DISQUALIFIED,
            'dq' => self::DISQUALIFIED,
            default => self::FINISHED,
        };
    }

    public static function matchUnfinishedOrPenalty(string $value): bool
    {
        return match (mb_strtolower($value)) {
            'dns' => true,
            'dnf' => true,
            'dsq' => true,
            default => false,
        };
    }

    public function unfinishedOrPenalty(): bool
    {
        return match ($this) {
            static::DID_NOT_START, static::DID_NOT_FINISH, static::DISQUALIFIED => true,
            default => false,
        };
    }

    public function finished(): bool
    {
        return $this === static::FINISHED;
    }

    public function disqualified(): bool
    {
        return $this === static::DISQUALIFIED;
    }

    public function didNotFinish(): bool
    {
        return $this === static::DID_NOT_FINISH;
    }

    public function didNotStart(): bool
    {
        return $this === static::DID_NOT_START;
    }

    public function localizedName(): string
    {
        return trans("result-status.types.{$this->name}");
    }
}
