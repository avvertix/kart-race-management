<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Championship;
use Illuminate\View\Component;

class ChampionshipPageLayout extends Component
{
    public function __construct(
        public Championship $championship,
    ) {}

    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {

        return view('layouts.championship');
    }
}
