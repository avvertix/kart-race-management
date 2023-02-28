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
                            <div class="flex gap-3 items-start">
                                <div class="flex gap-1">
                                    <span wire:loading wire:target="confirm({{ $item->getKey() }})">
                                        {{ __('Saving...') }}
                                    </span>

                                    @can('update', $item)
                                        @if ($item->confirmed_at)
                                            <x-jet-secondary-button class="inline-flex gap-1" wire:click.prevent="confirm({{ $item->getKey() }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                </svg>

                                                {{ __('Confirmed') }}
                                            </x-jet-button>
                                        @else
                                            <x-jet-button wire:click.prevent="confirm({{ $item->getKey() }})">{{ __('Confirm') }}</x-jet-button>
                                        @endif
                                    @endcan

                                    @can('create', \App\Model\Tire::class)
                                        @if ($item->tires_count == 0)
                                            <x-secondary-button-link href="{{ route('participants.tires.create', $item) }}">{{ __('Assign Tires') }}</x-secondary-button-link>
                                        @endif
                                    @endcan

                                    @can('viewAny', \App\Model\Tire::class)
                                        <x-secondary-button-link href="{{ route('participants.tires.index', $item) }}">{{ __('Tires') }}</x-secondary-button-link>
                                    @endcan
                                    
                                    @can('viewAny', \App\Model\Transponder::class)
                                        <x-secondary-button-link href="{{ route('participants.transponders.index', $item) }}">{{ __('Transponder') }}</x-secondary-button-link>
                                    @endcan
                                </div>
                                
                                @can('update', $item)
                                    <div class="flex gap-1">
                                        <x-secondary-button-link href="{{ route('participants.edit', $item) }}">{{ __('Edit') }}<span class="sr-only">, {{ $item->title }}</span></x-button-link>
                                    </div>
                                @endcan

                                <div class="flex gap-1">
                                    <x-jet-secondary-button wire:click.prevent="select(null)">
                                        {{ __('Collapse') }}
                                    </x-jet-button>
    
                                </div>
                            </div>
                        </div>
                        
                        @can('update', $item)
                            <div class="grid md:grid-cols-2 mb-2">
                                <p class="font-bold md:col-span-2">{{ __('Race participation price') }}</p>
                                <p class="flex gap-2 items-center">
                                    <x-price class="font-mono">{{ $item->price()->last() }}</x-price>

                                    @if ($item->use_bonus)
                                        <span class="text-sm bg-indigo-100 text-indigo-700 px-2 py-1 rounded">{{ __('Bonus')}}</span>
                                    @endif
                                </p>
                            </div>
                        @endcan

                        <div class="grid md:grid-cols-2 mb-2">
                            <p class="font-bold md:col-span-2">{{ __('Driver') }}</p>
                            <p>
                                {{ $item->first_name }}
                                {{ $item->last_name }}
                            </p>
                            <p>
                                {{ $item->driver['nationality'] }}
                                {{ $item->licence_type?->localizedName() }}
                                <span class="font-mono font-bold">{{ $item->driver['licence_number'] }}</span>
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
                            <div class="mb-2">
                                <p class="font-bold">{{ __('Competitor') }}</p>
                                @if ($item->competitor)
                                    <p>
                                        {{ $item->competitor['first_name'] }}
                                        {{ $item->competitor['last_name'] }}
                                    </p>
                                    <p>
                                        {{ $item->competitor['nationality'] }}
                                        {{ \App\Models\CompetitorLicence::from($item->competitor['licence_type'])->localizedName() }}
                                        <span class="font-mono font-bold">{{ $item->competitor['licence_number'] }}</span>
                                    </p>
                                    <p>
                                        {{ $item->competitor['email'] }}
                                        {{ $item->competitor['phone'] }}
                                    </p>
                                    <p>
                                        {{ __('Birth :place on :date', [
                                            'place' => $item->competitor['birth_place'],
                                            'date' => $item->competitor['birth_date'],
                                        ]) }}
                                    </p>
                                    <p>
                                        {{ __('Residence in :address, :city :province :postal_code', [
                                            'address' => $item->competitor['residence_address']['address'] ?? null,
                                            'city' => $item->competitor['residence_address']['city'] ?? null,
                                            'postal_code' => $item->competitor['residence_address']['postal_code'] ?? null,
                                            'province' => $item->competitor['residence_address']['province'] ?? null,
                                        ]) }}
                                    </p>
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

                        @can('update', $item)
                            <div class="mt-6">
                                <p class="font-bold md:col-span-2">{{ __('Participant id') }} <span class="font-mono">{{ $item->getKey() }}</span></p>
                            </div>
                        @endcan
                    
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
                        @if ($item->signatures_count == 0)
                            <span class="px-2 py-1 rounded bg-red-100 text-red-800">{{ __('Signature Missing') }}</span>
                        @elseif ($item->confirmed_at)
                            <span class="px-2 py-1 rounded bg-green-100 text-green-800">{{ __('Confirmed') }}</span>
                        @endif
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
