<div>
    <x-jet-label for="bib" value="{{ __('Number') }}*" />
    <x-jet-input-error for="bib" class="mt-2" />
    <x-jet-input id="bib" type="number" name="bib" class="mt-1 block w-full" :value="$value"  autofocus />
    <p class="text-zinc-600 mt-1">{{ __('Need a suggestion? try') }} 
        @forelse ($suggestions as $item)
            <button 
                type="button"
                class="text-orange-600 hover:text-orange-900 underline"
                wire:click="select('{{ $item }}')">
                {{ $item }}
            </button>{{ !$loop->last ? ',' : '' }}
        @empty
            
        @endforelse
    </p>
</div>
