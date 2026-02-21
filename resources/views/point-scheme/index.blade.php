<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Point Schemes') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('create', \App\Models\ChampionshipPointScheme::class)
            <x-button-link href="{{ route('championships.point-schemes.create', $championship) }}">
                {{ __('Add point scheme') }}
            </x-button-link>
        @endcan
    </x-slot>


    <x-table>
        <x-slot name="head">
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Name') }} &#9660;</th>
            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">{{ __('Edit') }}</span>
            </th>
        </x-slot>

        @forelse ($pointSchemes as $item)

            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6">
                    {{ $item->name }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    @can('update', $item)
                        <a href="{{ route('point-schemes.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->name }}</span></a>
                    @endcan
                </td>
            </tr>


        @empty
            <tr>
                <td colspan="2" class="px-3 py-4 space-y-2 text-center">
                    <p>{{ __('No point schemes at the moment') }}</p>
                    @can('create', \App\Models\ChampionshipPointScheme::class)
                        <p>
                            <x-button-link href="{{ route('championships.point-schemes.create', $championship) }}">
                                {{ __('Create a point scheme') }}
                            </x-button-link>
                        </p>
                    @endcan
                </td>
            </tr>
        @endforelse

    </x-table>

</x-championship-page-layout>
