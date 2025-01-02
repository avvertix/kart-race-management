<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Championship;
use App\Models\Race;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\Component;

class HighlightedRaces extends Component
{
    public $races;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Championship $championship)
    {
        $this->races = Race::query()
            ->when($championship->exists, function ($query, $state) use ($championship) {
                $query->where('championship_id', $championship->getKey());
            })
            ->withRegistrationOpen()
            ->orWhere(function (Builder $query) {
                $query->active();
            })
            ->orderBy('event_start_at')
            // ->with('championship')
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        return view('components.highlighted-races');
    }
}
