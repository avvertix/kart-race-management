<x-app-layout>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">Bib</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">Driver ▼</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">Category</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">...</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Edit</span>
                    </th>
                </x-slot>

                @forelse ($participants as $item)

                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6"><a href="{{ route('participants.show', $item) }}" class=" hover:text-orange-900">{{ $item->bib }}</a></td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->first_name }} {{ $item->last_name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->category }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">...</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            @can('update', $item)
                                <a href="{{ route('participants.edit', $item) }}" class="text-orange-600 hover:text-orange-900">Edit<span class="sr-only">, {{ $item->title }}</span></a>
                            @endcan
                        </td>
                    </tr>

                    
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                            <p>{{ __('No races at the moment') }}</p>
                            @can('create', \App\Model\Race::class)
                                <p>
                                    <x-button-link href="{{ route('championships.races.create', $championship) }}">
                                        {{ __('Create a race') }}
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
