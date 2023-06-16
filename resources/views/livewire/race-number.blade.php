<div>
    <x-jet-label for="bib" value="{{ __('Number') }}*" />
    <x-jet-input-error for="bib" class="mt-2" />
    <x-jet-input id="bib" type="number" name="bib" class="mt-1 block w-full" :value="$value"  autofocus />
    @if (!empty($suggestions))
        <p class="text-zinc-600 mt-1">{{ __('First time racing? Some currently free numbers:') }} 
            @foreach ($suggestions as $item)
                <button 
                    type="button"
                    class="text-orange-600 hover:text-orange-900 underline"
                    wire:click="select('{{ $item }}')">
                    {{ $item }}
                </button>{{ !$loop->last ? ',' : '' }}
            @endforeach
        </p>
    @endif
</div>
