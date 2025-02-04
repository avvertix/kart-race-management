<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Championship;
use App\Models\WildcardStrategy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WildcardSettings extends Component
{
    /**
     * The create API token form state.
     *
     * @var array
     */
    public $wildcardForm = [
        'enabled' => false,
        'strategy' => null,
    ];

    #[Locked]
    public $championship_id = null;

    /**
     * Mount the component.
     *
     * @return void
     */
    public function mount(Championship $championship)
    {
        $this->championship_id = $championship->getKey();

        $this->wildcardForm['enabled'] = $championship->wildcard?->enabled ?? false;
        $this->wildcardForm['strategy'] = $championship->wildcard?->strategy?->value ?? null;
    }

    public function updateWildcardSettings()
    {
        $this->resetErrorBag();

        Validator::make([
            'wildcard_enabled' => $this->wildcardForm['enabled'],
            'wildcard_strategy' => $this->wildcardForm['strategy'],
        ], [
            'wildcard_enabled' => ['required', 'boolean'],
            'wildcard_strategy' => ['required', 'integer', new Enum(WildcardStrategy::class)],
        ])->validateWithBag('updateWildcardSettings');

        auth()->user()->can('update', $this->championship);

        $this->championship->wildcard->enabled = $this->wildcardForm['enabled'];
        $this->championship->wildcard->strategy = WildcardStrategy::from((int)($this->wildcardForm['strategy']));
        $this->championship->save();

        $this->dispatch('saved');
    }

    /**
     * Get the current user of the application.
     *
     * @return mixed
     */
    public function getUserProperty()
    {
        return Auth::user();
    }

    public function getChampionshipProperty()
    {
        return Championship::find($this->championship_id);
    }

    public function getStrategiesProperty()
    {
        return WildcardStrategy::cases();
    }

    public function render()
    {
        return view('livewire.wildcard-settings');
    }
}
