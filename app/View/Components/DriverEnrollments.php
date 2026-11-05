<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Championship;
use App\Models\Enrollment;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

/**
 * The dashboard section proposing the championships open for enrollment, and listing the signed-in User's Enrollments
 * in this season's and future championships, whatever their status. Shown to Users allowed to enroll.
 */
class DriverEnrollments extends Component
{
    /**
     * How many of the User's Enrollments are listed.
     */
    private const ENROLLMENTS_LIMIT = 10;

    /** @var Collection<int, Championship> */
    public Collection $openChampionships;

    /** @var Collection<int, Enrollment> */
    public Collection $enrollments;

    public function __construct(int $limit = 5)
    {
        $user = auth()->user();

        if ($user !== null && ! $user->can('create', Enrollment::class)) {
            $user = null;
        }

        $this->openChampionships = $user === null ? new Collection : Championship::query()
            ->where('enrollment->enabled', true)
            ->orderByDesc('start_at')
            ->get()
            ->filter(fn (Championship $championship) => $championship->enrollment->isOpen())
            ->take($limit)
            ->values();

        $this->enrollments = $user === null ? new Collection : Enrollment::query()
            ->where('user_id', $user->getKey())
            ->whereHas('championship', fn ($query) => $query->where(fn ($query) => $query->whereNull('end_at')->orWhere('end_at', '>=', today()->startOfYear())))
            ->with(['championship', 'licence', 'licenceRequest', 'order'])
            ->latest()
            ->take(self::ENROLLMENTS_LIMIT)
            ->get();
    }

    public function shouldRender(): bool
    {
        return $this->openChampionships->isNotEmpty() || $this->enrollments->isNotEmpty();
    }

    public function render(): View|Closure|string
    {
        return view('components.driver-enrollments', [
            'timezone' => config('races.timezone'),
        ]);
    }
}
