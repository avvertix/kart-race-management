<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use ValueError;

class DateFormat implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if ($this->verifyFormat($value, 'Y-m-d') || $this->verifyFormat($value, 'd/m/Y')) {
            return;
        }

        $fail('The :attribute must follow the format Year-Month-Day (YYYY-MM-DD) or Day/Month/Year (DD/MM/YYYY), e.g. 2025-06-03 or 03/06/2025.');
    }

    protected function verifyFormat(string $value, string $format): bool
    {
        try {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

            return $date && $date->format($format) === $value;
        } catch (ValueError) {
            return false;
        }
    }
}
