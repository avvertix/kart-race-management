<?php

namespace App\Http\Livewire;

use App\Categories\Category;
use Livewire\Component;

class CategorySelector extends Component
{

    public $name;
    
    public $value;

    /**
     * @var \Illuminate\Support\Collection
     */
    public $categories;

    public $search;
    

    public function __construct()
    {
        $search = null;
    }
    
    public function render()
    {
        $this->categories = $this->search ? Category::search($this->search) : Category::all();


        return view('livewire.category-selector');
    }
}
