<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\GenerateRaceNumber;
use Livewire\Component;

class RaceNumber extends Component
{
    public $value;

    public $championship;

    public $suggestions = [];

    public function select($value)
    {
        $this->value = $value;
    }

    public function render(GenerateRaceNumber $generateRaceNumber)
    {
        $this->suggestions = $generateRaceNumber($this->championship);

        return view('livewire.race-number');
    }
}
