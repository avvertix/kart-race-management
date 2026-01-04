<div>

    <div class="sticky top-0 bg-zinc-100 print:bg-white pb-2">
        <!-- Search bar - full width on mobile, grows on desktop -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
            <x-search-input 
                id="participant_search" 
                wire:model.live.debounce.750ms="search" 
                wire:keydown.enter="$set('search', $event.target.value);" 
                type="text" 
                autofocus 
                placeholder="{{ __('Search participant using bib, name, last name or licence number') }}" 
                name="participant_search" 
                class="w-full md:grow"  
            />

            <!-- Filters row - stacked on mobile, inline on desktop -->
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 ">
                <!-- Category filter -->
                <select wire:model.live="filter_category" class="border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm w-full sm:w-auto">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <!-- Status filter (combined confirmation + transponder) -->
                <select wire:model.live="filter_status" class="border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm w-full sm:w-auto">
                    <option value="">{{ __('All participants') }}</option>
                    <option value="confirmed">{{ __('Confirmed') }}</option>
                    <option value="unconfirmed">{{ __('Unconfirmed') }}</option>
                    <option value="with-transponder">{{ __('With transponder') }}</option>
                    <option value="without-transponder">{{ __('Without transponder') }}</option>
                </select>

                <!-- Sort dropdown -->
                <select wire:model.live="sort" class="border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm w-full sm:w-auto">
                    <option value="bib">{{ __('Sort by: Race number') }}</option>
                    <option value="registration-date">{{ __('Sort by: Registration date') }}</option>
                    <option value="confirmed-date">{{ __('Sort by: Confirmation date') }}</option>
                    <option value="completed-date">{{ __('Sort by: Completion date') }}</option>
                </select>
            </div>
        </div>
    </div>

    <div class="h-4"></div>
    
    <x-table>
        <x-slot name="head">
            <th scope="col" class="w-4/12 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-600 sm:pl-6">
                {{ __('Driver') }}
            </th>
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

                        <p class="text-xs text-zinc-700">{{ __('registered at') }} <x-time :value="$item->created_at" /></p>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900">{{ $item->racingCategory?->name ?? __('no category') }} / {{ $item->engine }}</td>
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
                <td colspan="5" class="px-6 py-12 text-center">
                    <div class="space-y-3">
                        @php
                            $hasFilters = $search || $filter_category || $filter_status;
                        @endphp

                        @if ($hasFilters)
                            <!-- When filters are applied -->
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <p class="mt-4 text-sm font-medium text-gray-900">{{ __('No participants found') }}</p>
                                <p class="mt-2 text-sm text-gray-500">{{ __('No participants match the current filters.') }}</p>
                                
                                @if ($search)
                                    <p class="mt-1 text-sm text-gray-500">{{ __('Search: ":terms"', ['terms' => $search]) }}</p>
                                @endif
                                
                                <div class="mt-6">
                                    <button 
                                        wire:click="clearFilters"
                                        type="button"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                                    >
                                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('Clear all filters') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <!-- When no filters are applied -->
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="mt-4 text-sm font-medium text-gray-900">{{ __('No participants at the moment') }}</p>
                                <p class="mt-2 text-sm text-gray-500">{{ __('Get started by adding a participant to this race.') }}</p>
                            </div>
                            
                            @can('create', \App\Model\Participant::class)
                                <div class="mt-6">
                                    <x-button-link href="{{ route('races.participants.create', $race) }}">
                                        {{ __('Add participant') }}
                                    </x-button-link>
                                </div>
                            @endcan
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </x-table>

</div>
