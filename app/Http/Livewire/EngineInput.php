<?php

namespace App\Http\Livewire;

use Livewire\Component;

class EngineInput extends Component
{

    public $value;

    public $suggestions;

    public function select($value)
    {
        $this->value = $value;
    }

    public function render()
    {
        $this->suggestions = collect(config('engine.manufacturers'))->take(6);

        return view('livewire.engine-input');
    }
}
