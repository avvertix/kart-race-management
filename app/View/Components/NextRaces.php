<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Race;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class NextRaces extends Component
{
    public Collection $races;

    public function __construct(int $limit = 5)
    {
        $this->races = Race::query()
            ->nextRaces()
            ->orderBy('event_start_at')
            ->withCount('participants')
            ->take($limit)
            ->get();
    }

    public function render(): View|Closure|string
    {
        $canView = auth()->user()?->can('viewAny', Race::class) ?? false;

        return view('components.next-races', [
            'races' => $this->races,
            'canView' => $canView,
        ]);
    }
}
