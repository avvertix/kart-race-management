<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Features\LicencesAndEnrollments;
use App\Models\Championship;
use App\Models\Enrollment;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Laravel\Pennant\Feature;

class ChampionshipsList extends Component
{
    public Collection $championships;

    public function __construct(int $limit = 5)
    {
        $user = auth()->user();
        $countsOverdue = $user !== null && $user->can('review', Enrollment::class) && Feature::for($user)->active(LicencesAndEnrollments::class);

        $this->championships = Championship::query()
            ->withCount('races')
            ->when($countsOverdue, fn ($query) => $query->withCount(['enrollments as overdue_enrollments_count' => fn ($query) => $query->overdue()]))
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
