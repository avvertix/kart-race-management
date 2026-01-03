<div class="relative">
    <x-label for="driver_search" value="{{ __('Search for existing driver') }}" />
    <p class="mb-1 text-sm text-zinc-600">
        <span wire:loading.remove wire:target="search">{{ __('Start typing to search for existing drivers across all championships') }}</span>
        <span wire:loading wire:target="search"> 
            {{ __('Searching driver...') }}
        </span>
    </p>
    <div class="relative mt-1">
        <x-input id="driver_search" wire:model.live.debounce.750ms="search" type="text" placeholder="{{ __('Search by name, surname or licence number') }}" name="driver_search" class="block w-full pr-10" />
        @if($search)
            <button type="button" wire:click="clearSearch" class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        @endif
    </div>

    @if ($this->participants)

        <div class="z-10 max-h-96 overflow-y-scroll absolute p-2 w-full bg-white border border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm space-y-2 mt-1">
            @forelse ($this->participants as $item)

                <button
                    type="button"
                    wire:click="selectParticipant('{{ $item->uuid }}')"
                    class="-mx-2 p-2 grid grid-cols-5 items-start hover:bg-orange-300 focus:bg-orange-300 active:bg-orange-400 w-full text-left rounded"
                >
                    <span class="font-mono text-sm block shrink-0 bg-gray-100 px-2 py-1 rounded mr-2">
                        {{ $item->driver['licence_number'] }}
                    </span>
                    <span class="col-span-2">
                        {{ $item->first_name }} {{ $item->last_name }}
                        <span class="text-sm text-zinc-600 block">
                            {{ $item->driver['email'] ?? __('No email') }}
                        </span>
                    </span>
                    <span class="col-span-2 text-sm text-zinc-600 text-right">{{ $item->championship->title }}</span>
                </button>

            @empty

                <p class="text-zinc-600">
                    {{ __('No driver matching your search ":terms"', ['terms' => $search]) }}
                </p>

            @endforelse
        </div>
    @endif

</div>
