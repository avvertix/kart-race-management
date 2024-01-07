<?php

namespace App\Livewire;

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

    public $sort;

    protected $queryString = [
        'search' => ['except' => '', 'as' => 's'],
        'selectedParticipant' => ['except' => '', 'as' => 'pid'],
        'sort' => ['except' => '', 'as' => 'order'],
    ];

    public function mount($race)
    {
        $this->race = $race;
        $this->search = null;
        $this->sort = 'bib';
    }

    public function select($item)
    {
        $this->selectedParticipant = $item;
    }
    
    public function sorting($option)
    {
        $this->sort = $option === 'bib' ? 'bib' : 'registration-date';
    }
    
    public function confirm($item)
    {
        // TODO: add some validation and/or an action to be reused
        Participant::findOrFail($item)->update(['confirmed_at' => now()]);
    }
    
    public function markAsComplete($item)
    {
        // TODO: add some validation and/or an action to be reused
        Participant::findOrFail($item)->update(['registration_completed_at' => now()]);
    }
    
    public function markAsOutOfZone($item, $outOfZone = true)
    {
        // TODO: add some validation and/or an action to be reused
        Participant::findOrFail($item)->markOutOfZone($outOfZone);
    }
    
    public function resendSignatureNotification($item)
    {
        // TODO: add some validation and/or an action to be reused
        $participant = Participant::findOrFail($item);

        if($participant->hasSignedTheRequest()){
            return;
        }

        $participant->sendConfirmParticipantNotification();
    }

    public function render()
    {
        $this->participants = $this->race->participants()
            ->withCount('tires')
            ->withCount('signatures')
            ->withCount('transponders')
            ->with('payments')
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
            ->orderBy( $this->sort === 'registration-date' ? 'created_at' : 'bib', 'asc')
            ->get();

        return view('livewire.participant-listing');
    }
}
