<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Championships') }}
        </h2>
        @can('create', \App\Model\Championship::class)
            <div>
                <x-button-link href="{{ route('championships.create') }}">
                    {{ __('Create a championship') }}
                </x-button-link>
            </div>
        @endcan
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Title') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Period') }} ▼</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </th>
                </x-slot>

                @forelse ($championships as $item)

                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6"><a href="{{ route('championships.show', $item->uuid) }}" class=" hover:text-orange-900">{{ $item->title }}</a></td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->period }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            @can('update', $item)
                                <a href="{{ route('championships.edit', $item->uuid) }}" class="text-orange-600 hover:text-orange-900">Edit<span class="sr-only">, {{ $item->title }}</span></a>
                            @endcan
                        </td>
                    </tr>

                                
                @empty
                    <tr>
                        <td colspan="3" class="px-3 py-4 space-y-2 text-center">
                            <p>{{ __('No championships at the moment') }}</p>
                            @can('create', \App\Model\Championship::class)
                                <p>
                                    <x-button-link href="{{ route('championships.create') }}">
                                        {{ __('Create a championship') }}
                                    </x-button-link>
                                </p>
                            @endcan
                        </td>
                    </tr>
                @endforelse
            
            </x-table>

            {{ $championships->links() }}
        
        </div>
    </div>
</x-app-layout>
