<x-app-layout>
    <x-slot name="header">

        @section('actions')

            @can('create', \App\Model\ChampionshipTire::class)
                <x-button-link href="{{ route('championships.bib-reservations.create', $championship) }}">
                    {{ __('Reserve a race number') }}
                </x-button-link>
            @endcan

        @endsection

        @include('championship.partials.heading')
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Race number and driver name') }} ▼</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Contact') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Reserved until') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </th>
                </x-slot>

            

            
            @forelse ($reservations as $item)

                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6">

                        <p><a href="{{ route('bib-reservations.show', $item) }}" class=" hover:text-orange-900">
                            <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $item->bib }}</span>
                            {{ $item->driver }}
                        </a></p>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        {{ $item->contact_email }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        @if ($item->reservation_expires_at)
                            <x-time :value="$item->reservation_expires_at" />
                        @else
                            {{ __('end of championship') }}
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @can('update', $item)
                            <a href="{{ route('bib-reservations.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->name }}</span></a>
                        @endcan
                    </td>
                </tr>

                
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                        <p>{{ __('No race numbers reserved at the moment.') }}</p>
                        @can('create', \App\Model\ChampionshipTire::class)
                            <p>
                                <x-button-link href="{{ route('championships.bib-reservations.create', $championship) }}">
                                    {{ __('Reserve a race number') }}
                                </x-button-link>
                            </p>
                        @endcan
                    </td>
                </tr>
            @endforelse
            
            </x-table>
        </div>
    </div>
</x-app-layout>
