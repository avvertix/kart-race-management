<div class="relative">
    <x-search-input id="participant_search" wire:model.debounce.750ms="search" wire:keydown.enter="$set('search', $event.target.value);" type="text" placeholder="{{ __('Search participant within championship') }}" name="participant_search" class="block w-full"  />

    @if ($this->participants)
        
        <div class="z-10 max-h-96 overflow-y-scroll absolute p-2 w-full bg-white border border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm space-y-2">
            @forelse ($this->participants as $item)

                <a class="-mx-2 p-2 flex items-start hover:bg-orange-300 focus:bg-orange-300 active:bg-orange-400" href="{{ route('races.participants.create', ['race' => $race->uuid, 'from' => $item->uuid]) }}">
                    <span class="font-mono text-lg block w-1/5 text-center shrink-0 bg-gray-100 group-hover:bg-orange-200 px-2 py-1 rounded mr-2">{{ $item->bib }}</span>
                    <span>
                        {{ $item->first_name }} {{ $item->last_name }}
                        <span class="text-sm text-zinc-600 block">
                            {{ $item->categoryConfiguration()?->name ?? $item->category }} / {{ $item->engine }}
                        </span>
                    </span>
                </a>

            @empty

                <p class="text-zinc-600">
                    {{ __('No participant matching your search ":terms"', ['terms' => $search]) }}
                </p>

            @endforelse
        </div>
    @endif

</div>
