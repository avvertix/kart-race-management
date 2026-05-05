<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Championship;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class ChampionshipsList extends Component
{
    public Collection $championships;

    public function __construct(int $limit = 5)
    {
        $this->championships = Championship::query()
            ->withCount('races')
            ->where('start_at', '>=', today()->startOfYear())
            ->orderByDesc('start_at')
            ->take($limit)
            ->get();
    }

    public function render(): View|Closure|string
    {
        $canView = auth()->user()?->can('viewAny', Championship::class) ?? false;

        return view('components.championships-list', [
            'championships' => $this->championships,
            'canView' => $canView,
        ]);
    }
}
