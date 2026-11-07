<?php

declare(strict_types=1);

namespace App\Support;

class LapTime
{
    /**
     * Convert a MyLaps time ("54.201", "1:02.345", "1:02:03.456") to milliseconds.
     */
    public static function toMilliseconds(?string $value): ?int
    {
        $value = mb_trim((string) $value);

        if ($value === '' || ! preg_match('/^(?:\d+:){0,2}\d+(?:[.,]\d+)?$/', $value)) {
            return null;
        }

        $seconds = 0.0;

        foreach (explode(':', str_replace(',', '.', $value)) as $part) {
            $seconds = ($seconds * 60) + (float) $part;
        }

        return (int) round($seconds * 1000);
    }
}
