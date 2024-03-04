<x-app-layout>
    <x-slot name="title">
        {{ __('Races') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Races') }}
        </h2>
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <x-highlighted-races class="mb-6" />

        <x-table>
            <x-slot name="head">
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Title') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Date') }} ▼</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Track') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">{{ __('Edit') }}</span>
                </th>
            </x-slot>

            @forelse ($races as $item)

                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6"><a href="{{ route('races.show', $item->uuid) }}" class=" hover:text-orange-900">{{ $item->title }}</a></td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->period }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->track }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500"><x-race-status :value="$item->status" /></td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @can('update', $item)
                            <a href="{{ route('races.edit', $item->uuid) }}" class="text-orange-600 hover:text-orange-900">Edit<span class="sr-only">, {{ $item->title }}</span></a>
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
