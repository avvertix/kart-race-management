<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Str;
use InvalidArgumentException;

enum RunType: int
{
    case WARM_UP = 10;
    case QUALIFY_SERIES = 15;
    case QUALIFY = 20;
    case HEAT = 25;
    case RACE_1 = 30;
    case RACE_2 = 40;

    /**
     * Qualifying is split in series (turni) that the timetable calls "Serie", "Gruppo", "Group".
     */
    private const SERIES_KEYWORDS = ['serie', 'series', 'gruppo', 'group'];

    public static function fromString(?string $value): static
    {
        if (blank($value)) {
            throw new InvalidArgumentException('Cannot identify run from an empty text.');
        }

        if (Str::contains($value, ['batteria', 'batterie', 'heat'], ignoreCase: true)) {
            return self::HEAT;
        }

        if (Str::contains($value, ['gara 1', 'race 1', 'gara1', 'gara-1', 'race1', 'race-1', 'prefinale', 'pre-finale'], ignoreCase: true)) {
            return self::RACE_1;
        }

        if (Str::contains($value, ['gara 2', 'race 2', 'gara2', 'race2', 'gara-2', 'race-2', 'finale', 'final'], ignoreCase: true)) {
            return self::RACE_2;
        }

        if (Str::contains($value, ['qualifiche', 'qualifying', 'prove cronometrate', 'cronometrate'], ignoreCase: true)) {
            return Str::contains($value, self::SERIES_KEYWORDS, ignoreCase: true) ? self::QUALIFY_SERIES : self::QUALIFY;
        }

        if (Str::contains($value, ['prove libere', 'libere', 'warm up', 'warmup', 'warm-up', 'practice'], ignoreCase: true)) {
            return self::WARM_UP;
        }

        if (Str::contains($value, self::SERIES_KEYWORDS, ignoreCase: true)) {
            return self::QUALIFY_SERIES;
        }

        throw new InvalidArgumentException("Cannot identify run from [{$value}].");
    }

    /**
     * @return array<int, int>
     */
    public static function workingSessionValues(): array
    {
        return collect(self::cases())->filter->isWorkingSession()->map->value->values()->all();
    }

    public function isPractice(): bool
    {
        return $this === self::WARM_UP;
    }

    /**
     * A qualifying session, either the combined one or one of its series.
     */
    public function isQualify(): bool
    {
        return in_array($this, [self::QUALIFY_SERIES, self::QUALIFY]);
    }

    /**
     * One of the series (groups) a category qualified in, combined afterwards in a single qualifying.
     */
    public function isQualifySeries(): bool
    {
        return $this === self::QUALIFY_SERIES;
    }

    public function isHeat(): bool
    {
        return $this === self::HEAT;
    }

    /**
     * Heats are race sessions too: they are classified by laps and total time.
     */
    public function isRace(): bool
    {
        return in_array($this, [self::HEAT, self::RACE_1, self::RACE_2]);
    }

    /**
     * Series and heats are working sessions: their combined classification is what is scored and published.
     */
    public function isWorkingSession(): bool
    {
        return in_array($this, [self::QUALIFY_SERIES, self::HEAT]);
    }

    public function localizedName(): string
    {
        return trans("run-type.types.{$this->name}");
    }
}
