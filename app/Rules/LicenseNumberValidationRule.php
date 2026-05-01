<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class LicenseNumberValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * The goal is to block clear placeholders or unwanted licence numbers. Without relying on national or
     * international bodies it is impossible to fully validate licence numbers. This rule catches obvious
     * fakes: all-zeros, country names, racing status codes, placeholder text, and date-like patterns.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(':attribute is not a valid licence number.');

            return;
        }

        if (blank($value)) {
            $fail(':attribute is not a valid licence number.');

            return;
        }

        $value = Str::ascii(mb_trim($value));

        if (mb_strlen($value) < 3) {
            $fail(':attribute is not a valid licence number.');

            return;
        }

        // A licence number must contain at least one digit; purely alphabetical values are country
        // names, federation codes, or other non-licence text
        if (! preg_match('/\d/', $value)) {
            $fail(':attribute is not a valid licence number.');

            return;
        }

        // Values made up entirely of special characters (///, ..., ;;;) are not licence numbers
        if (! preg_match('/[a-zA-Z0-9]/', $value)) {
            $fail(':attribute is not a valid licence number.');

            return;
        }

        if (preg_match('/^\d+$/', $value)) {
            $this->validateNumericLicence($value, $fail);
        }
    }

    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    private function validateNumericLicence(string $value, Closure $fail): void
    {
        // All-zeros entries (0000, 0000000) are common placeholders
        if (preg_match('/^0+$/', $value)) {
            $fail(':attribute is not a valid licence number.');

            return;
        }

    }
}
