<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Participant;
use Livewire\Component;

class DriverSearch extends Component
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $participants;

    public $search;

    public function mount()
    {
        $this->search = null;
    }

    public function clearSearch()
    {
        $this->search = null;
    }

    public function selectParticipant($participantUuid)
    {
        $participant = Participant::where('uuid', $participantUuid)->first();

        if ($participant) {
            $this->dispatch('driver-selected', [
                'driver' => $participant->driver['first_name'].' '.$participant->driver['last_name'],
                'licence' => $participant->driver['licence_number'] ?? '',
                'email' => $participant->driver['email'] ?? '',
            ]);

            // Clear the search after selection
            $this->search = null;
        }
    }

    public function render()
    {
        $this->participants = ! $this->search ? null : Participant::where(function ($query) {
            $query->where('bib', e($this->search))
                ->orWhere('first_name', 'LIKE', e($this->search).'%')
                ->orWhere('last_name', 'LIKE', e($this->search).'%')
                ->orWhere('driver_licence', hash('sha512', $this->search))
                ->orWhere('competitor_licence', hash('sha512', $this->search));
        })
            ->orderBy('created_at', 'desc')
            ->with('championship')
            ->take(10)
            ->get()
            ->unique(function ($participant) {
                // Group by driver licence to avoid duplicates
                return $participant->driver_licence;
            })
            ->values();

        return view('livewire.driver-search');
    }
}
