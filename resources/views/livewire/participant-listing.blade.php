<div>

    <x-search-input id="participant_search" wire:model="search" type="text" autofocus placeholder="{{ __('Search participant using bib, name, last name or licence number') }}" name="participant_search" class="block w-full sticky top-0"  />

    <div class="h-4"></div>
    
    <x-table>
        <x-slot name="head">
            <th scope="col" class="w-4/12 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-600 sm:pl-6">{{ __('Bib') }} ▼ / {{ __('Driver') }}</th>
            <th scope="col" class="w-3/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Category / Engine') }}</th>
            <th scope="col" class="w-2/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Licence') }}</th>
            <th scope="col" class="w-1/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Status') }}</th>
            <th scope="col" class="w-2/12 relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">{{ __('Edit') }}</span>
            </th>
        </x-slot>

        @forelse ($participants as $item)

            @if ($selectedParticipant && $selectedParticipant === $item->getKey())
                <tr class="relative">
                    <td colspan="5" class="px-3 py-4 space-y-2 border-2 border-orange-300">
                        @include('participant.partials.expanded-details')
                    </td>
                </tr>
                
            @else
                <tr class="relative">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-zinc-900 sm:pl-6">

                        <a href="{{ route('participants.show', $item) }}"
                            wire:click.prevent="select({{ $item->getKey() }})"
                            class=" hover:text-orange-900  font-medium group">
                            <span class="font-mono text-lg inline-block bg-gray-100 group-hover:bg-orange-200 px-2 py-1 rounded mr-2">{{ $item->bib }}</span>
                            {{ $item->first_name }} {{ $item->last_name }}
                        </a>

                        <span wire:loading wire:target="select({{ $item->getKey() }})">
                            {{ __('Opening...') }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900">{{ $item->category()?->name ?? $item->category }} / {{ $item->engine }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900">{{ $item->licence_type?->localizedName() }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900">
                        @include('participant.partials.status')
                    </td>
                    <td class=" py-4 pl-3 pr-4 text-right font-medium sm:pr-6 space-x-2">

                        @can('create', \App\Model\Tire::class)
                            @if ($item->tires_count == 0)
                                <a href="{{ route('participants.tires.create', $item) }}" class="whitespace-nowrap text-orange-600 hover:text-orange-900">{{ __('Assign tires') }}</a>
                            @endif
                        @endcan
                        
                        @can('create', \App\Model\Transponder::class)
                            @if ($item->transponders_count == 0)
                                <a href="{{ route('participants.transponders.create', $item) }}" class="whitespace-nowrap text-orange-600 hover:text-orange-900">{{ __('Assign transponder') }}</a>
                            @endif
                        @endcan

                        @unless ($item->registration_completed_at)
                            @can('update', $item)
                                <a href="{{ route('participants.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->title }}</span></a>
                            @endcan
                        @endunless
                    </td>
                </tr>
            @endif

            
        @empty
            <tr>
                <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                    @if ($search)
                    
                        <p>{{ __('No participant matching your search ":terms"', ['terms' => $search]) }}</p>
                        
                    @else
                        <p>{{ __('No participants at the moment') }}</p>
                    @endif
                    @can('create', \App\Model\Participant::class)
                        <p>
                            <x-button-link href="{{ route('races.participants.create', $race) }}">
                                {{ __('Add participant') }}
                            </x-button-link>
                        </p>
                    @endcan
                </td>
            </tr>
        @endforelse
    </x-table>

</div>
