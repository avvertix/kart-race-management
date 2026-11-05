<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\EnrollmentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

/**
 * The dashboard section proposing the championships open for enrollment, and listing the signed-in User's Enrollments
 * waiting for the organizer.
 */
class DriverEnrollments extends Component
{
    /** @var Collection<int, Championship> */
    public Collection $openChampionships;

    /** @var Collection<int, Enrollment> */
    public Collection $pendingEnrollments;

    public function __construct(int $limit = 5)
    {
        $user = auth()->user();

        $this->openChampionships = $user === null ? new Collection : Championship::query()
            ->where('enrollment->enabled', true)
            ->orderByDesc('start_at')
            ->get()
            ->filter(fn (Championship $championship) => $championship->enrollment->isOpen())
            ->take($limit)
            ->values();

        $this->pendingEnrollments = $user === null ? new Collection : Enrollment::query()
            ->where('user_id', $user->getKey())
            ->where('status', EnrollmentStatus::Submitted)
            ->with(['championship', 'licence', 'licenceRequest', 'order'])
            ->latest()
            ->take($limit)
            ->get();
    }

    public function shouldRender(): bool
    {
        return $this->openChampionships->isNotEmpty() || $this->pendingEnrollments->isNotEmpty();
    }

    public function render(): View|Closure|string
    {
        return view('components.driver-enrollments', [
            'timezone' => config('races.timezone'),
        ]);
    }
}
