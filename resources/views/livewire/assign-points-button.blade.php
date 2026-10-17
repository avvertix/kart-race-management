<div>
    <x-secondary-button type="button" wire:click="openAssignPoints" >
        {{ __('Assign points') }}
    </x-secondary-button>

    <flux:modal wire:model.self="showModal" class="md:w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Select point scheme') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Multiple point schemes are available. Select which one to use for assigning points.') }}</flux:text>
            </div>

            <div class="space-y-2">
                @foreach ($pointSchemes as $scheme)
                    <label class="flex items-center gap-3 p-3 border rounded-md cursor-pointer hover:bg-zinc-50 has-checked:border-orange-400 has-checked:bg-orange-50">
                        <input type="radio" wire:model="selectedPointScheme" value="{{ $scheme['id'] }}" class="text-orange-500 focus:ring-orange-500">
                        <span class="font-medium">{{ $scheme['name'] }}</span>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <x-secondary-button>
                        {{ __('Cancel') }}
                    </x-secondary-button>
                </flux:modal.close>

                <x-button type="button" wire:click="assignPoints">
                    {{ __('Assign points') }}
                </x-button>
            </div>
        </div>
    </flux:modal>
</div>
