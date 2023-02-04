<?php

namespace App\Models;

use Illuminate\Support\HtmlString;
use NumberFormatter;

enum Currency: string
{
    case EUR = 'EUR';


    /**
     * Format the given amount using the specific currency
     * 
     * @param \Illuminate\Support\HtmlString|int $amount
     */
    public function format($amount): string
    {
        $amount = $amount instanceof HtmlString ? intval(trim($amount->toHtml())) : $amount;

        $fmt = new NumberFormatter( 'en_EN', NumberFormatter::CURRENCY );

        return $fmt->formatCurrency($amount/100, $this->value);
    }
}
