<x-app-layout>
    <x-slot name="header">

        @section('actions')

            @can('create', \App\Model\Bonus::class)
                <x-button-link href="{{ route('championships.bonuses.create', $championship) }}">
                    {{ __('Add bonus') }}
                </x-button-link>
            @endcan

        @endsection

        @include('championship.partials.heading')
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Driver') }} ▼</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Bonus type') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Amount') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Remaining') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </th>
                </x-slot>
            
            @forelse ($bonuses as $item)

                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm  text-zinc-900 sm:pl-6">
                        <p><a href="{{ route('bonuses.show', $item) }}" class="font-medium hover:text-orange-900">{{ $item->driver }}</a></p>
                        <p>{{ $item->driver_licence }}</p>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        {{ $item->bonus_type->localizedName() }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        {{ $item->amount }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        {{ $item->remaining() }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @can('update', $item)
                            <a href="{{ route('bonuses.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->driver }}</span></a>
                        @endcan
                    </td>
                </tr>

                
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                        <p>{{ __('No bonus or discounts') }}</p>
                        @can('create', \App\Model\Bonus::class)
                            <p>
                                <x-button-link href="{{ route('championships.bonuses.create', $championship) }}">
                                    {{ __('Add a bonus') }}
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
