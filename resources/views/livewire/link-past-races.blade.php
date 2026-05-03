<div class="space-y-4">

    {{-- Search --}}
    <div class="flex gap-2 items-start">
        <div class="flex flex-1">
            <x-input
                wire:model="search"
                wire:keydown.enter="performSearch"
                type="text"
                class="w-full"
                placeholder="{{ __('Search licence number...') }}"
            />
            <x-input-error for="search" class="mt-1" />
            <x-input-error for="action" class="mt-1" />
        </div>
        <x-button wire:click="performSearch" wire:loading.attr="disabled" type="button">
            <span wire:loading.remove wire:target="performSearch">{{ __('Search') }}</span>
            <span wire:loading wire:target="performSearch">{{ __('Searching…') }}</span>
        </x-button>
        @if ($verifiedSearch)
            <button wire:click="clearSearch" type="button" class="text-sm text-zinc-500 underline self-center whitespace-nowrap">
                {{ __('Clear') }}
            </button>
        @endif
    </div>

    {{-- Results --}}
    @if ($participants->isNotEmpty())
        <div class="space-y-2">
            @php
                $linkableCount = $participants->filter(
                    fn ($p) => ! in_array($p->uuid, $linkedUuids) && $p->claimed_by !== auth()->id() && $p->added_by !== auth()->id()
                )->count();
            @endphp
            @if ($linkableCount > 1)
                <div class="flex justify-end">
                    <button wire:click="linkAll" wire:loading.attr="disabled" type="button" class="text-sm text-zinc-700 underline hover:text-zinc-900">
                        {{ __('Link all') }}
                    </button>
                </div>
            @endif
            @foreach ($participants as $participant)
                @php
                    $isLinked = $participant->claimed_by === auth()->id() || $participant->added_by === auth()->id() || in_array($participant->uuid, $linkedUuids);
                @endphp
                <div class="flex items-center justify-between p-3 bg-zinc-50 rounded border border-zinc-200">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-orange-100 text-orange-700 shrink-0">{{ $participant->bib }}</span>
                            <span class="font-medium text-sm truncate">{{ $participant->first_name }} {{ $participant->last_name }}</span>
                        </div>
                        <p class="text-xs text-zinc-500 mt-0.5 truncate">
                            {{ $participant->race?->title ?? '-' }}
                            @if ($participant->race?->championship)
                                &middot; {{ $participant->race->championship->title }}
                            @endif
                            &middot; {{ $participant->created_at->toDateString() }}
                        </p>
                        @if (! $isLinked)
                            <p class="text-xs text-zinc-400 mt-0.5">
                                @if (! empty($participant->competitor))
                                    {{ __('Driver + Competitor') }}
                                @else
                                    {{ __('Driver only') }}
                                @endif
                                @if (! empty($participant->mechanic))
                                    + {{ __('Mechanic') }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="ml-4 shrink-0">
                        @if ($isLinked)
                            <span class="text-xs text-green-600 font-medium">{{ __('Linked') }}</span>
                        @else
                            <button wire:click="link('{{ $participant->uuid }}')" wire:loading.attr="disabled" type="button" class="text-sm text-zinc-700 underline hover:text-zinc-900">
                                {{ __('Link') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($verifiedSearch)
        <p class="text-sm text-zinc-500">{{ __('No past registrations found for ":search".', ['search' => $verifiedSearch]) }}</p>
    @endif

</div>
