<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Support\Collection;
use Livewire\Component;

class RaceParticipantSearch extends Component
{
    public Race $race;

    public string $search = '';

    public ?Collection $participants = null;

    public function mount(Race $race): void
    {
        $this->race = $race;
    }

    public function selectParticipant(int $participantId): void
    {
        $participant = Participant::query()
            ->where('race_id', $this->race->getKey())
            ->findOrFail($participantId);

        $this->dispatch('participant-selected', [
            'id' => $participant->getKey(),
            'uuid' => $participant->uuid,
            'first_name' => $participant->first_name,
            'last_name' => $participant->last_name,
            'bib' => $participant->bib,
            'category_id' => $participant->category_id,
        ]);

        $this->reset('search', 'participants');
    }

    public function render()
    {
        if (mb_strlen($this->search) >= 1) {
            $this->participants = Participant::query()
                ->where('race_id', $this->race->getKey())
                ->where(function ($query) {
                    $query->where('bib', e($this->search))
                        ->orWhere('first_name', 'LIKE', e($this->search).'%')
                        ->orWhere('last_name', 'LIKE', e($this->search).'%');
                })
                ->orderBy('bib', 'asc')
                ->get();
        } else {
            $this->participants = null;
        }

        return view('livewire.race-participant-search');
    }
}
