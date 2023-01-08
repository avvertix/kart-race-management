<?php

namespace App\Rules;

use App\Categories\Category;
use Illuminate\Contracts\Validation\InvokableRule;

class ExistsCategory implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if(!Category::find($value)){
            $fail('validation.category')->translate();
        }
    }
}
