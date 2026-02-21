<div class="relative">
    <x-search-input id="race_participant_search" wire:model.live.debounce.750ms="search" type="text" placeholder="{{ __('Search participant within race') }}" name="race_participant_search" class="block w-full" />

    @if ($this->participants)
        <div class="z-10 max-h-96 overflow-y-scroll absolute p-2 w-full bg-white border border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm space-y-2">
            @forelse ($this->participants as $item)
                <button type="button" wire:click="selectParticipant({{ $item->getKey() }})" class="w-full text-left -mx-2 p-2 flex items-start hover:bg-orange-300 focus:bg-orange-300 active:bg-orange-400 cursor-pointer">
                    <span class="font-mono text-lg block w-1/5 text-center shrink-0 bg-gray-100 px-2 py-1 rounded mr-2">{{ $item->bib }}</span>
                    <span>
                        {{ $item->first_name }} {{ $item->last_name }}
                        <span class="text-sm text-zinc-600 block">
                            {{ $item->racingCategory?->name ?? __('no category') }}
                        </span>
                    </span>
                </button>
            @empty
                <p class="text-zinc-600">
                    {{ __('No participant matching your search ":terms"', ['terms' => $search]) }}
                </p>
            @endforelse
        </div>
    @endif
</div>
