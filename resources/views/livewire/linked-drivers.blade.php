<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Drivers and Competitors') }}
        </h2>
        
        <div id="actions">

        </div>
            
        
    </div>
</x-slot>

<div class="pb-12">
    <div class="px-4 sm:px-6 lg:px-8 space-y-8">

        @teleport('#actions')
            <div class="flex justify-end">
                <x-button wire:click="toggleForm" type="button">
                    {{ __('Claim a participation') }}
                </x-button>
            </div>
        @endteleport
        

        {{-- Claim form --}}
        @if ($showForm)
            <div class="p-4 bg-white rounded border border-zinc-200 space-y-3">
                <p class="text-sm text-zinc-600">
                    {{ __('Link race registrations to your account. Search by licence number and then press link next to the registration you want to connect. Connected registrations can be used to speed-up the race registration process.') }}
                </p>

                {{-- Search --}}
                <div class="flex gap-2 items-start">
                    <div class="flex-1">
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

                {{-- Search results --}}
                @if ($participants->isNotEmpty())
                    <div class="space-y-2">
                        @php
                            $linkableCount = $participants->filter(
                                fn ($p) => ! in_array($p->uuid, $linkedUuids) && $p->claimed_by !== auth()->id() && $p->added_by !== auth()->id()
                            )->count();
                        @endphp
                        @if ($linkableCount > 1)
                            <div class="flex justify-end">
                                <x-button wire:click="linkAll" wire:loading.attr="disabled" type="button" >
                                    {{ __('Link all') }}
                                </x-button>
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
                                        <x-button wire:click="link('{{ $participant->uuid }}')" wire:loading.attr="disabled" type="button">
                                            {{ __('Link') }}
                                        </x-button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($verifiedSearch)
                    <p class="text-sm text-zinc-500">{{ __('No past registrations found for ":search".', ['search' => $verifiedSearch]) }}</p>
                @endif
            </div>
        @endif

        {{-- Linked participants --}}
        @if ($linkedParticipants->isNotEmpty())
            <p class="text-sm">
                {{ __('Driver registrations linked to your account. These are registrations done for past races you can reuse.') }}
            </p>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Driver') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Category') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Competitor') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Last race') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900"></th>
                </x-slot>

                @foreach ($linkedParticipants as $participant)
                    <tr>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $participant->bib }}</span>
                            {{ $participant->first_name }} {{ $participant->last_name }}
                            <span class="font-mono block">{{ $participant->driver_licence_number }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            {{ $participant->racingCategory?->name }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            @if ($participant->competitor)
                                {{ $participant->competitor['first_name'] }} {{ $participant->competitor['last_name'] }}
                            @else
                            &mdash;
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            {{ $participant->race?->title ?? '-' }}
                            @if ($participant->race?->championship)
                                &middot; {{ $participant->race->championship->title }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            @if ($nextRaces[$participant->uuid] ?? null)
                                <a href="{{ route('races.registration.create', ['race' => $nextRaces[$participant->uuid], 'from' => $participant->uuid]) }}" class="text-sm text-zinc-700 underline hover:text-zinc-900">
                                    {{ __('Register to :race', ['race' => $nextRaces[$participant->uuid]->title]) }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @endif

    </div>
</div>
