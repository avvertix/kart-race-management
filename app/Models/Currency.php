<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentSlot;
use NumberFormatter;

enum Currency: string
{
    case EUR = 'EUR';

    /**
     * Format the given amount using the specific currency
     *
     * @param  ComponentSlot|int  $amount
     */
    public function format($amount): string
    {
        $amount = $amount instanceof HtmlString || $amount instanceof ComponentSlot ? (int) (trim($amount->toHtml())) : $amount;

        $fmt = new NumberFormatter('en_EN', NumberFormatter::CURRENCY);

        return $fmt->formatCurrency($amount / 100, $this->value);
    }
}
