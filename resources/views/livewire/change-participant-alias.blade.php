<div>
    @if($isEditing)
        <form wire:submit="save" class="flex flex-col gap-2">
            <div class="grid md:grid-cols-3 gap-2">
                <div>
                    <x-label for="name" value="{{ __('Name alias') }}" />
                    <x-input wire:model="name" type="text" class="mt-1 w-full" placeholder="{{ __('Alternative name') }}" />
                </div>
                
                <div>
                    <x-label for="category" value="{{ __('Category alias') }}" />
                    <x-input wire:model="category" type="text" class="mt-1 w-full" placeholder="{{ __('Alternative category name') }}" />
                </div>

                <div>
                    <x-label for="bib" value="{{ __('Race number alias') }}" />
                    <x-input wire:model="bib" type="text" class="mt-1 w-full" placeholder="{{ __('Alternative race number') }}" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-secondary-button type="submit">
                    {{ __('Save') }}
                </x-secondary-button>

                <x-secondary-button type="button" wire:click="cancelEditing">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <span x-data="{ shown: false }" 
                      x-show="shown" 
                      x-transition 
                      x-init="@this.on('saved', () => { shown = true; setTimeout(() => shown = false, 2000); })"
                      class="text-sm text-green-600">
                    {{ __('Saved!') }}
                </span>
            </div>

            @if($name || $category || $bib)
                <p class="text-sm text-zinc-600">
                    {{ __('Preview') }}: {{ $this->getAliasesString() }}
                </p>
            @endif
        </form>
    @else
        <div class="flex gap-2 items-start">
            <div class="flex-1">
                @if($name || $category || $bib)
                    <p class="text-sm">{{ $this->getAliasesString() }}</p>
                @else
                    <p class="text-sm text-zinc-500 italic">{{ __('No aliases defined') }}</p>
                @endif
            </div>
            
            <x-secondary-button type="button" wire:click="startEditing" class="shrink-0 text-sm">
                {{ __('Edit') }}
            </x-secondary-button>
        </div>
    @endif
</div>