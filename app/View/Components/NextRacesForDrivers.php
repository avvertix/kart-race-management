<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Participant;
use App\Models\Race;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\Component;

class NextRacesForDrivers extends Component
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
        $user = auth()->user();
        $canView = $user?->can('viewAny', Race::class) ?? false;

        $registrationsByRace = $this->buildRegistrationsByRace($user);

        return view('components.next-races-for-drivers', [
            'races' => $this->races,
            'canView' => $canView,
            'registrationsByRace' => $registrationsByRace,
        ]);
    }

    private function buildRegistrationsByRace(mixed $user): SupportCollection
    {
        if (! $user) {
            return collect();
        }

        $raceIds = $this->races->pluck('id')->all();
        $championshipIds = $this->races->pluck('championship_id')->unique()->all();

        $linkedParticipants = Participant::registered()
            ->where(fn ($q) => $q->where('claimed_by', $user->id)->orWhere('added_by', $user->id))
            ->whereIn('championship_id', $championshipIds)
            ->latest()
            ->get()
            ->unique(fn (Participant $p) => $p->championship_id.'-'.$p->driver_licence);

        $licenceHashes = $linkedParticipants->pluck('driver_licence')->filter()->unique()->values()->all();

        $existingInRaces = $licenceHashes !== []
            ? Participant::registered()
                ->whereIn('race_id', $raceIds)
                ->whereIn('driver_licence', $licenceHashes)
                ->get()
                ->groupBy('race_id')
            : collect();

        return $this->races->mapWithKeys(function (Race $race) use ($linkedParticipants, $existingInRaces) {
            $existingForRace = $existingInRaces->get($race->id, collect());

            $entries = $linkedParticipants
                ->where('championship_id', $race->championship_id)
                ->map(fn (Participant $linked) => [
                    'linked' => $linked,
                    'participation' => $existingForRace->firstWhere('driver_licence', $linked->driver_licence),
                ])
                ->values();

            return [$race->uuid => $entries];
        });
    }
}
