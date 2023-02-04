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
                <span class="sr-only">Edit</span>
            </th>
        </x-slot>

        @forelse ($participants as $item)

            @if ($selectedParticipant && $selectedParticipant === $item->getKey())
                <tr class="relative">
                    <td colspan="5" class="px-3 py-4 space-y-2 border-2 border-orange-300">
                        <div class="flex justify-between">
                            <div>
                                <button type="button" class="text-3xl font-bold flex items-center gap-2"
                                    wire:click.prevent="select(null)"
                                    >
                                    <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $item->bib }}</span>
                                    <span>{{ $item->first_name }} {{ $item->last_name }}</span>
                                </button>
                                <p class="mb-1 text-xl">{{ $item->category()->name }} / {{ $item->engine }}</p>
                                <p class="mb-6 text-xl">{{ $item->tire()->name }}</p>
                            </div>
                            <div>
                                <x-jet-button wire:click.prevent="select(null)">{{ __('Collapse') }}</x-jet-button>

                                @can('update', $item)
                                    <x-button-link href="{{ route('participants.edit', $item) }}">{{ __('Edit') }}<span class="sr-only">, {{ $item->title }}</span></x-button-link>
                                @endcan
                            </div>
                        </div>
                        
                        <div class="grid md:grid-cols-2 mb-2">
                            <p class="font-bold md:col-span-2">{{ __('Driver') }}</p>
                            <p>
                                {{ $item->first_name }}
                                {{ $item->last_name }}
                            </p>
                            <p>
                                {{ $item->driver['nationality'] }}
                                {{ $item->licence_type?->name }}
                                {{ $item->driver['licence_number'] }}
                            </p>
                            <p>
                                {{ $item->driver['email'] }}
                                {{ $item->driver['phone'] }}
                            </p>
                            <p>
                                {{ __('Birth :place on :date', [
                                    'place' => $item->driver['birth_place'],
                                    'date' => $item->driver['birth_date'],
                                ]) }}
                            </p>
                            <p>
                                {{ __('Medical certificate expires on :date', [
                                    'date' => $item->driver['medical_certificate_expiration_date'],
                                ]) }}
                            </p>
                            <p>
                                {{ __('Residence in :address, :city :province :postal_code', [
                                    'address' => $item->driver['residence_address']['address'] ?? null,
                                    'city' => $item->driver['residence_address']['city'] ?? null,
                                    'postal_code' => $item->driver['residence_address']['postal_code'] ?? null,
                                    'province' => $item->driver['residence_address']['province'] ?? null,
                                ]) }}
                            </p>
                        </div>
                        <div class="grid md:grid-cols-2">
                            <div class="grid md:grid-cols-3 mb-2">
                                <p class="font-bold md:col-span-3">{{ __('Competitor') }}</p>
                                @if ($item->competitor)
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_first_name" value="{{ __('Name') }}*" />
                                        {{ $item->competitor['first_name'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_last_name" value="{{ __('Surname') }}*" />
                                        {{ $item->competitor['last_name'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_licence_type" value="{{ __('Licence Type') }}*" />
                                        {{ $item->competitor['licence_type'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_licence_number" value="{{ __('Licence Number') }}*" />
                                        {{ $item->competitor['licence_number'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_nationality" value="{{ __('Nationality') }}*" />
                                        {{ $item->competitor['nationality'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_email" value="{{ __('E-Mail') }}*" />
                                        {{ $item->competitor['email'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_phone" value="{{ __('Phone number') }}*" />
                                        {{ $item->competitor['phone'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_birth_date" value="{{ __('Birth date') }}*" />
                                        {{ $item->competitor['birth_date'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_birth_place" value="{{ __('Birth place') }}*" />
                                        {{ $item->competitor['birth_place'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="competitor_residence" value="{{ __('Residence address') }}*" />
                                        {{ $item->competitor['residence_address']['address'] ?? null }}
                                        {{ $item->competitor['residence_address']['city'] ?? null }}
                                        {{ $item->competitor['residence_address']['postal_code'] ?? null }}
                                        {{ $item->competitor['residence_address']['province'] ?? null }}
                                    </div>
                                @else
                                    <p>{{ __('No competitor specified') }}</p>
                                @endif
                            </div>
                            <div class="mb-2">
                                <p class="font-bold">{{ __('Mechanic') }}</p>
                                @if ($item->mechanic)
                                    <p>
                                        {{ $item->mechanic['name'] }}
                                        {{ $item->mechanic['licence_number'] }}
                                    </p>
                                @else
                                    <p>{{ __('No mechanic specified') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-6 grid md:grid-cols-2">
                        
                            @foreach ($item->vehicles as $vehicle)
                                <div class="grid sm:grid-cols-2 gap-1">
                                    <div class="">
                                        <span class="text-sm text-zinc-500 block">{{ __('Chassis') }}</span>
                                        {{ $vehicle['chassis_manufacturer'] }}
                                    </div>
                                    <div class="">
                                        <span class="text-sm text-zinc-500 block">{{ __('Engine') }}</span>
                                        {{ $vehicle['engine_manufacturer'] }}
                                        {{ $vehicle['engine_model'] }}
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-sm text-zinc-500 block">{{ __('Oil') }}</span>
                                        {{ $vehicle['oil_manufacturer'] }}
                                        {{ $vehicle['oil_type'] }}
                                        {{ $vehicle['oil_percentage'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    
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
                    <td class="whitespace-nowrap px-3 py-4 text-zinc-900">...</td>
                    <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right font-medium sm:pr-6">

                        @can('update', $item)
                            <a href="{{ route('participants.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->title }}</span></a>
                        @endcan
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
