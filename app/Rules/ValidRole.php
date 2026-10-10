<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\Role;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRole implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || Role::tryFrom($value) === null) {
            $fail(__('The :attribute must be a valid role.'));
        }
    }
}
