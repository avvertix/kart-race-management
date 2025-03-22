<div>

<x-form-section submit="updateWildcardSettings">
        <x-slot name="title">
            {{ __('Wildcard') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Configure when championship entrants are considered wildcards (i.e. the do not score points).') }}
        </x-slot>

        <x-slot name="form">
            
            <div class="col-span-6 sm:col-span-4">
                <x-input-error for="wildcard_enabled" class="mb-2" />
                <x-label for="wildcard_enabled" class="flex items-center gap-2">
                    <x-checkbox id="wildcard_enabled" name="wildcard_enabled"  wire:model="wildcardForm.enabled"/>
                    {{ __('Track wildcards in this championship') }}
                </x-label>
            </div>
            
            <div class="col-span-6 sm:col-span-4">
                <x-label for="wildcard_strategy" value="{{ __('Wildcard strategy') }}" />
                <x-input-error for="wildcard_strategy" class="mb-2" />
                <select name="wildcard_strategy" id="wildcard_strategy" class="mt-1 block w-full" wire:model="wildcardForm.strategy">
                    <option value="">{{ __('Select a strategy') }}</option>
                @foreach ($this->strategies as $item)
                    <option value="{{ $item->value }}">{{ $item->localizedName() }}</option>
                @endforeach
                </select>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label for="bonus_amount" value="{{ __('Required Amount of bonuses (minimum 1)') }}" />
                <x-input-error for="bonus_amount" class="mb-2" />
                <x-input id="bonus_amount" type="number" class="mt-1 block w-full" wire:model="wildcardForm.bonus_amount" min="1" />
            </div>

        </x-slot>

        <x-slot name="actions">
            <x-action-message class="mr-3" on="saved">
                {{ __('Saved.') }}
            </x-action-message>

            <x-button>
                {{ __('Save') }}
            </x-button>
        </x-slot>
    </x-form-section>

</div>