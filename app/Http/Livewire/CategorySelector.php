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
    

    public function __construct()
    {
        $this->categories = Category::all();
    }

    public function render()
    {
        return view('livewire.category-selector');
    }
}
