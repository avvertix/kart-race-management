<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        <x-button-link href="{{ route('calendar.championship.races', ['championship' => $championship->uuid, 'format' => 'ics']) }}">
            {{ __('Download calendar') }}
        </x-button-link>

        @can('create', \App\Model\Race::class)
            <x-button-link href="{{ route('championships.races.create', $championship) }}">
                {{ __('Add race') }}
            </x-button-link>
        @endcan

        @can('create', \App\Model\Race::class)
            <x-button-link href="{{ route('championships.races.import.create', $championship) }}">
                {{ __('Import races') }}
            </x-button-link>
        @endcan

        @can('update', $championship)
            <x-button-link href="{{ route('championships.edit', $championship) }}">
                {{ __('Edit championship') }}
            </x-button-link>
        @endcan
    </x-slot>

        <x-highlighted-races :championship="$championship" class="mb-10" />

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
                            <a href="{{ route('races.edit', $item->uuid) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->title }}</span></a>
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
</x-championship-page-layout>
