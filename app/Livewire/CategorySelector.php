<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;

class CategorySelector extends Component
{

    public $name;
    
    public $value;

    /**
     * @var \Illuminate\Support\Collection
     */
    public $categories;
    
    /**
     * @var \App\Models\Championship
     */
    public $championship;

    public $search;
    

    public function __construct()
    {
        $search = null;
    }
    
    public function render()
    {
        $this->categories = $this->search
            ? $this->championship->categories()->enabled()->orderBy('name', 'ASC')->search($this->search)->get()
            : $this->championship->categories()->orderBy('name', 'ASC')->enabled()->get();

        return view('livewire.category-selector');
    }
}
