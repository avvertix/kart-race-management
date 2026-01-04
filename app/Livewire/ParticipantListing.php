<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Participant;
use App\Models\Race;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ParticipantListing extends Component
{
    public $selectedParticipant;

    /**
     * @var Collection
     */
    public $participants;

    /**
     * @var Race
     */
    public $race;

    public $search;

    public $sort;

    public $filter_category;

    public $filter_status;

    protected $queryString = [
        'search' => ['except' => '', 'as' => 's'],
        'selectedParticipant' => ['except' => '', 'as' => 'pid'],
        'sort' => ['except' => '', 'as' => 'order'],
        'filter_category' => ['except' => '', 'as' => 'category'],
        'filter_status' => ['except' => '', 'as' => 'status'],
    ];

    public function mount($race)
    {
        $this->race = $race;
        $this->search = null;
        $this->sort = 'bib';
        $this->filter_status = '';
    }

    public function select($item)
    {
        $this->selectedParticipant = $item;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filter_category = '';
        $this->filter_status = '';
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

        if ($participant->hasSignedTheRequest()) {
            return;
        }

        $participant->sendConfirmParticipantNotification();
    }

    #[Computed()]
    public function categories()
    {
        return $this->race->championship->categories()
            ->enabled()
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        $this->participants = $this->race->participants()
            ->withCount('tires')
            ->withCount('signatures')
            ->withCount('transponders')
            ->with(['payments', 'reservations' => function ($query) {
                $query->notExpired()->withoutLicence()->inChamphionship($this->race->championship_id);
            }])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('bib', e($search))
                        ->orWhere('first_name', 'LIKE', e($search).'%')
                        ->orWhere('last_name', 'LIKE', e($search).'%')
                        ->orWhere('driver_licence', hash('sha512', $search))
                        ->orWhere('competitor_licence', hash('sha512', $search));
                });
            })
            ->when($this->filter_category, function ($query, $category_id) {
                $query->whereHas('racingCategory', function ($query) use ($category_id) {
                    $query->where('id', $category_id);
                });
            })
            ->when($this->filter_status !== '', function ($query) {
                match ($this->filter_status) {
                    'confirmed' => $query->whereNotNull('confirmed_at'),
                    'unconfirmed' => $query->whereNull('confirmed_at'),
                    'with-transponder' => $query->has('transponders'),
                    'without-transponder' => $query->doesntHave('transponders'),
                    default => null,
                };
            });

        // Apply sorting
        $sortField = match ($this->sort) {
            'registration-date' => 'created_at',
            'confirmed-date' => 'confirmed_at',
            'completed-date' => 'registration_completed_at',
            default => 'bib',
        };

        $this->participants = $this->participants
            ->orderBy($sortField, 'asc')
            ->get();

        return view('livewire.participant-listing');
    }
}
