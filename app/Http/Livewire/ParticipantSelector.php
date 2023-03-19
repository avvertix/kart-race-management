<?php

namespace App\Http\Livewire;

use App\Models\Participant;
use Livewire\Component;

class ParticipantSelector extends Component
{
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

    public function render()
    {
        $this->participants = !$this->search ? null : Participant::whereChampionshipId($this->race->championship_id)
            ->where('race_id', '!=', $this->race->getKey())
            ->where(function($query){
                $query->where('bib', e($this->search))
                    ->orWhere('first_name', 'LIKE', e($this->search).'%')
                    ->orWhere('last_name', 'LIKE', e($this->search).'%')
                    ->orWhere('driver_licence', hash('sha512', $this->search))
                    ->orWhere('competitor_licence', hash('sha512', $this->search))
                    ;
            })
            ->orderBy('bib', 'asc')
            ->get();

        return view('livewire.participant-selector');
    }
}
