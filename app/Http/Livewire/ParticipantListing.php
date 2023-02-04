<?php

namespace App\Http\Livewire;

use App\Models\Race;
use Illuminate\Support\Collection;
use Livewire\Component;

class ParticipantListing extends Component
{
    public $selectedParticipant;

    public $participants;

    public $search;

    public function __construct(public $race)
    {
        $this->search = null;
    }

    public function select($item)
    {
        $this->selectedParticipant = $item;
    }

    public function render()
    {
        $this->participants = $this->race->participants()->when($this->search, function($query, $search){
            $query->where('bib', e($search))
                ->orWhere('first_name', 'LIKE', e($search).'%')
                ->orWhere('last_name', 'LIKE', e($search).'%')
                ->orWhere('driver_licence', hash('sha512', $search))
                ->orWhere('competitor_licence', hash('sha512', $search))
                ;
        })->get();

        return view('livewire.participant-listing');
    }
}
