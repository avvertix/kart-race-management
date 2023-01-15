<?php

namespace App\Support;


interface Describable
{
    /**
     * Run the description of the entry.
     *
     * @return string
     */
    public function description(): string;
}
