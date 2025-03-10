<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\View\Component;

class LanguageSelector extends Component
{
    public $supportedLanguages = ['en', 'it'];

    public $currentLanguage;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->currentLanguage = app()->currentLocale();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|Closure|string
     */
    public function render()
    {
        return view('components.language-selector');
    }
}
