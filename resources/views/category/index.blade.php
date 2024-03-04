<x-app-layout>
    <x-slot name="title">
        {{ __('Categories') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">

        @section('actions')

            @can('create', \App\Model\Category::class)
                <x-button-link href="{{ route('championships.categories.create', $championship) }}">
                    {{ __('Add category') }}
                </x-button-link>
            @endcan

        @endsection

        @include('championship.partials.heading')
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Name') }} ▼</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Tire') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Short Name') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </th>
                </x-slot>
            
            @forelse ($categories as $item)

                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm  text-zinc-900 sm:pl-6">
                        <p><a href="{{ route('categories.show', $item) }}" class="font-medium hover:text-orange-900">{{ $item->name }}</a></p>
                        <p>{{ $item->description }}</p>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        @if ($item->tire)
                            <a href="{{ route('tire-options.show', $item->tire) }}" target="_blank">{{ $item->tire?->name ?? __('All tires') }}</a>
                        @else
                            {{ __('All tires') }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        {{ $item->short_name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        <span class="inline-block px-2 py-1 text-sm rounded-full {{ $item->enabled ? 'bg-lime-100 text-lime-800' : 'bg-zinc-100 text-zinc-800' }}">
                            {{ $item->enabled ? __('active') : __('inactive') }}
                        </span>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @can('update', $item)
                            <a href="{{ route('categories.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->name }}</span></a>
                        @endcan
                    </td>
                </tr>

                
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                        <p>{{ __('No categories at the moment') }}</p>
                        @can('create', \App\Model\Category::class)
                            <p>
                                <x-button-link href="{{ route('championships.categories.create', $championship) }}">
                                    {{ __('Create a category') }}
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
