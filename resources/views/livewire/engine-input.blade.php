<div class="mt-1 block w-full">
    <x-jet-input id="vehicle_engine_manufacturer" type="text" name="vehicle_engine_manufacturer" class="mt-1 block w-full" :value="$value"  autocomplete="engine_manufacturer" />
    <div class="text-zinc-600">
        {{ __('Suggestions:')}}
        @forelse ($suggestions as $item)
            <button 
                type="button"
                class="text-orange-600 hover:text-orange-900 underline"
                wire:click="select('{{ $item }}')">
                {{ $item }}
            </button>{{ !$loop->last ? ',' : '' }}
        @empty
            
        @endforelse
    </div>
</div>
