<div>
    <button type="button" wire:click="openAssignPoints" class="underline cursor-pointer text-sm">
        {{ __('Assign points') }}
    </button>

    <x-dialog-modal wire:model.live="showModal">
        <x-slot name="title">
            {{ __('Select point scheme') }}
        </x-slot>

        <x-slot name="content">
            <p class="text-sm text-zinc-600 mb-4">{{ __('Multiple point schemes are available. Select which one to use for assigning points.') }}</p>

            <div class="space-y-2">
                @foreach ($pointSchemes as $scheme)
                    <label class="flex items-center gap-3 p-3 border rounded-md cursor-pointer hover:bg-zinc-50 has-[:checked]:border-orange-400 has-[:checked]:bg-orange-50">
                        <input type="radio" wire:model="selectedPointScheme" value="{{ $scheme['id'] }}" class="text-orange-500 focus:ring-orange-500">
                        <span class="font-medium">{{ $scheme['name'] }}</span>
                    </label>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ml-3" wire:click="assignPoints">
                {{ __('Assign points') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
