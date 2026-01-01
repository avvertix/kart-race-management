<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\GenerateRaceNumber;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use Livewire\Component;

class FreeRaceNumbers extends Component
{
    public Championship $championship;

    public $checkNumber = '';

    public $checkResult = null; // null | 'available' | 'taken'

    public $takenBy = null; // Participant or BibReservation info

    public $takenByType = null; // 'participant' | 'reservation'

    public function mount(Championship $championship)
    {
        $this->championship = $championship;
    }

    public function checkAvailability()
    {
        // Validate input: must be a number
        $this->validate([
            'checkNumber' => 'required|numeric|min:1',
        ]);

        $bib = (int) $this->checkNumber;

        // First check if taken by a participant
        $participant = Participant::where('championship_id', $this->championship->getKey())
            ->where('bib', $bib)
            ->first();

        if ($participant) {
            $this->checkResult = 'taken';
            $this->takenBy = $participant->full_name;
            $this->takenByType = 'participant';

            return;
        }

        // Check if reserved
        $reservation = BibReservation::where('championship_id', $this->championship->getKey())
            ->where('bib', $bib)
            ->notExpired()
            ->first();

        if ($reservation) {
            $this->checkResult = 'taken';
            $this->takenBy = $reservation->driver_name ?? __('Reserved');
            $this->takenByType = 'reservation';

            return;
        }

        // If neither, it's available
        $this->checkResult = 'available';
        $this->takenBy = null;
        $this->takenByType = null;
    }

    public function clearCheck()
    {
        $this->reset(['checkNumber', 'checkResult', 'takenBy', 'takenByType']);
    }

    public function render(GenerateRaceNumber $generateRaceNumber)
    {
        $freeNumbers = $generateRaceNumber($this->championship, 10);

        return view('livewire.free-race-numbers', [
            'freeNumbers' => $freeNumbers,
        ]);
    }
}
