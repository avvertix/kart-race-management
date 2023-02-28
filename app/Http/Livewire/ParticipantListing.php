<?php

namespace App\Http\Livewire;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Support\Collection;
use Livewire\Component;

class ParticipantListing extends Component
{
    public $selectedParticipant;

    /**
     * @var \Illuminate\Support\Collection
     */
    public $participants;

    /**
     * @var \App\Models\Race
     */
    public $race;

    public $search;

    public function __construct($race)
    {
        $this->race = $race;
        $this->search = null;
    }

    public function select($item)
    {
        $this->selectedParticipant = $item;
    }
    
    public function confirm($item)
    {
        // TODO: add some validation and an action to be reused
        Participant::findOrFail($item)->update(['confirmed_at' => now()]);
    }

    public function render()
    {
        $this->participants = $this->race->participants()
            ->withCount('tires')
            ->withCount('signatures')
            ->withCount('transponders')
            ->when($this->search, function($query, $search){
                $query->where(function($query) use($search){
                    $query->where('bib', e($search))
                        ->orWhere('first_name', 'LIKE', e($search).'%')
                        ->orWhere('last_name', 'LIKE', e($search).'%')
                        ->orWhere('driver_licence', hash('sha512', $search))
                        ->orWhere('competitor_licence', hash('sha512', $search))
                        ;
                });
            })
            ->orderBy('bib', 'asc')
            ->get();

        return view('livewire.participant-listing');
    }
}
