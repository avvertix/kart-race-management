<div class="min-w-40">
    @if($isEditing)
        <form wire:submit="save" class="flex flex-col gap-2">       
            <x-textarea wire:model="notes" class="mt-1 w-full text-sm" rows="2" autofocus />

            <div class="flex items-center gap-2">
                <x-button type="submit">
                    {{ __('Save') }}
                </x-button>

                <x-secondary-button type="button" wire:click="cancelEditing">
                    {{ __('Cancel') }}
                </x-secondary-button>

                
            </div>
        </form>
    @else
        <div class="flex gap-2 items-start">
            <div class="flex-1">
                @if($notes)
                    <p class="text-sm whitespace-pre-line">{{ $notes }}</p>
                @else
                    <p class="text-sm text-zinc-500 italic">{{ __('No notes') }}</p>
                @endif
            </div>
            
            <x-secondary-button type="button" wire:click="startEditing" class="shrink-0 text-sm">
                {{ __('Edit') }}
            </x-secondary-button>

            <span x-data="{ shown: false }" 
                      x-show="shown" 
                      x-transition 
                      x-init="@this.on('saved', () => { shown = true; setTimeout(() => shown = false, 2000); })"
                      class="text-sm text-green-600">
                    {{ __('Saved!') }}
                </span>
        </div>
    @endif
</div>