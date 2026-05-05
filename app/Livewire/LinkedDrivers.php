<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Drivers and Competitors')]
class LinkedDrivers extends Component
{
    public string $search = '';

    public ?string $verifiedSearch = null;

    /** @var array<string> */
    public array $linkedUuids = [];

    public bool $showForm = false;

    public function mount(): void
    {
        $user = auth()->user();

        $this->showForm = ! Participant::registered()
            ->where(function ($q) use ($user) {
                $q->where('claimed_by', $user->id)->orWhere('added_by', $user->id);
            })
            ->exists();
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    public function performSearch(): void
    {
        $this->validate(['search' => ['required', 'string', 'min:3']]);
        $this->verifiedSearch = $this->search;
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->verifiedSearch = null;
    }

    public function link(string $uuid): void
    {
        Gate::authorize('drivers:create');

        $user = auth()->user();

        $participant = Participant::registered()
            ->where('uuid', $uuid)
            ->first();

        abort_unless($participant !== null, 404);

        if (! $this->canAct($user, $participant)) {
            $this->addError('action', __('You are not authorized to link this participation.'));

            return;
        }

        if ($participant->claimed_by === null) {
            $participant->update(['claimed_by' => $user->id]);
        }

        $this->linkedUuids[] = $uuid;
    }

    public function linkAll(): void
    {
        Gate::authorize('drivers:create');

        if (! $this->verifiedSearch) {
            $this->addError('action', __('Please perform a search first.'));

            return;
        }

        $user = auth()->user();
        $licenceHash = hash('sha512', $this->verifiedSearch);

        Participant::registered()
            ->where('driver_licence', $licenceHash)
            ->where(function ($q) use ($user) {
                $q->where(function ($q) use ($user) {
                    $q->where('claimed_by', '!=', $user->id)->orWhereNull('claimed_by');
                })->where(function ($q) use ($user) {
                    $q->where('added_by', '!=', $user->id)->orWhereNull('added_by');
                });
            })
            ->whereHas('championship', fn ($q) => $q->where('start_at', '>=', today()->startOfYear()))
            ->get()
            ->each(function (Participant $participant) use ($user) {
                if ($participant->claimed_by === null) {
                    $participant->update(['claimed_by' => $user->id]);
                }

                if (! in_array($participant->uuid, $this->linkedUuids)) {
                    $this->linkedUuids[] = $participant->uuid;
                }
            });
    }

    public function render()
    {
        $user = auth()->user();

        $linkedParticipants = Participant::registered()
            ->where(function ($q) use ($user) {
                $q->where('claimed_by', $user->id)->orWhere('added_by', $user->id);
            })
            ->with('race.championship')
            ->latest()
            ->get()
            ->unique('driver_licence');

        $participants = collect();

        if ($this->verifiedSearch) {
            $licenceHash = hash('sha512', $this->verifiedSearch);

            $participants = Participant::registered()
                ->where('driver_licence', $licenceHash)
                ->where(function ($q) use ($user) {
                    $q->where(function ($q) use ($user) {
                        $q->where('claimed_by', '!=', $user->id)->orWhereNull('claimed_by');
                    })->where(function ($q) use ($user) {
                        $q->where('added_by', '!=', $user->id)->orWhereNull('added_by');
                    });
                })
                ->whereHas('championship', fn ($q) => $q->where('start_at', '>=', today()->startOfYear()))
                ->with('race.championship')
                ->latest()
                ->get();
        }

        $nextRaces = $linkedParticipants->mapWithKeys(function (Participant $participant) {
            $nextRace = Race::withRegistrationOpen()
                ->where('championship_id', $participant->championship_id)
                ->whereDoesntHave('participants', fn ($q) => $q->where('driver_licence', $participant->driver_licence))
                ->orderBy('event_start_at')
                ->first();

            return [$participant->uuid => $nextRace];
        });

        return view('livewire.linked-drivers', [
            'linkedParticipants' => $linkedParticipants,
            'participants' => $participants,
            'nextRaces' => $nextRaces,
        ]);
    }

    private function canAct(mixed $user, Participant $participant): bool
    {
        if ($participant->claimed_by === $user->id || $participant->added_by === $user->id) {
            return true;
        }

        if ($this->verifiedSearch) {
            $licenceHash = hash('sha512', $this->verifiedSearch);

            return $licenceHash === $participant->driver_licence;
        }

        return false;
    }
}
