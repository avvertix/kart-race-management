<?php

declare(strict_types=1);

namespace App\Support;

interface Describable
{
    /**
     * Run the description of the entry.
     */
    public function description(): string;
}
