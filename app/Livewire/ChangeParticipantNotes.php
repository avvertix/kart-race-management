<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Participant;
use Livewire\Component;

class ChangeParticipantNotes extends Component
{
    public Participant $participant;

    public $notes;

    public $isEditing = false;

    public $originalNotes;

    public function mount()
    {
        $this->notes = $this->participant->notes;
        $this->originalNotes = $this->notes;
    }

    public function startEditing()
    {
        $this->isEditing = true;
    }

    public function cancelEditing()
    {
        $this->isEditing = false;
        $this->notes = $this->originalNotes;
    }

    public function save()
    {
        $this->participant->update([
            'notes' => $this->notes,
        ]);

        $this->originalNotes = $this->notes;
        $this->isEditing = false;
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.change-participant-notes');
    }
}
