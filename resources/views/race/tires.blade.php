<x-app-layout>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-flow-col-dense gap-4">
                @foreach ($tires as $item)
                    <div class="text-lg p-4 bg-white shadow rounded">
                        <p>{{ $item['name'] }}</p>
                        <p class="text-3xl font-black">{{ $item['total'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="h-4"></div>
    
    <x-table>
        <x-slot name="head">
            <th scope="col" class="w-4/12 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-600 sm:pl-6">{{ __('Bib') }} ▼ / {{ __('Driver') }}</th>
            <th scope="col" class="w-3/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Category / Engine') }}</th>
            <th scope="col" class="w-2/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Tires') }}</th>
            <th scope="col" class="w-1/12 px-3 py-3.5 text-left text-sm font-semibold text-zinc-600">{{ __('Status') }}</th>
            <th scope="col" class="w-2/12 relative py-3.5 pl-3 pr-4 sm:pr-6">
                &nbsp;
            </th>
        </x-slot>

        @forelse ($participants as $item)

            <tr class="relative">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-zinc-900 sm:pl-6 font-medium">

                    <span class="font-mono text-lg inline-block bg-gray-100 group-hover:bg-orange-200 px-2 py-1 rounded mr-2">{{ $item->bib }}</span>
                    {{ $item->first_name }} {{ $item->last_name }}

                </td>
                <td class="whitespace-nowrap px-3 py-4 text-zinc-900">{{ $item->category()?->name ?? $item->category }} / {{ $item->engine }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-zinc-900">{{ $item->tires_count }} &times; {{ $item->category()?->tire()?->name ?? __('unspecified') }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-zinc-900">
                    @if ($item->registration_completed_at)
                        <span class="px-2 py-1 rounded bg-blue-100 text-blue-800">{{ __('Registration completed') }}</span>
                    @elseif ($item->signatures_count == 0)
                        <span class="px-2 py-1 rounded bg-red-100 text-red-800">{{ __('Signature Missing') }}</span>
                    @elseif ($item->confirmed_at)
                        <span class="px-2 py-1 rounded bg-green-100 text-green-800">{{ __('Confirmed') }}</span>
                    @endif
                </td>
                <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right font-medium sm:pr-6 space-x-2">

                    @can('viewAny', \App\Model\Tire::class)
                        <a href="{{ route('participants.tires.index', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('View tires') }}</a>
                    @endcan

                    @can('create', \App\Model\Tire::class)
                        @if ($item->tires_count < 5)
                            <a href="{{ route('participants.tires.create', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Add tires') }}</a>
                        @endif
                    @endcan
                </td>
            </tr>

            
        @empty
            <tr>
                <td colspan="5" class="px-3 py-4 space-y-2 text-center">

                    <p>{{ __('No participants with assigned tires') }}</p>
                </td>
            </tr>
        @endforelse
    </x-table>


        </div>
    </div>
</x-app-layout>
