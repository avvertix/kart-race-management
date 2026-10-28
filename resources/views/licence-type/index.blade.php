<x-app-layout>
    <x-slot name="title">
        {{ __('Licence types') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                {{ __('Licence types') }}
            </h2>
            @can('create', \App\Models\LicenceType::class)
                <x-button-link href="{{ route('licence-types.create') }}">
                    {{ __('Add licence type') }}
                </x-button-link>
            @endcan
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Name') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Provider') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Role') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Grades') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Documents') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </th>
                </x-slot>

                @forelse ($licenceTypes as $item)
                    <tr>
                        <td class="py-4 pl-4 pr-3 text-sm text-zinc-900 sm:pl-6">
                            <p class="font-medium">{{ $item->name }}</p>
                            <p class="text-zinc-500">{{ $item->description }}</p>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->provider->label() }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->role->label() }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->grades_count }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->documents_count }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            @if ($item->active)
                                <span class="inline-block px-2 py-1 text-sm rounded-full bg-lime-100 text-lime-800">{{ __('active') }}</span>
                            @else
                                <span class="inline-block px-2 py-1 text-sm rounded-full bg-zinc-100 text-zinc-800">{{ __('inactive') }}</span>
                            @endif
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            @can('update', $item)
                                <a href="{{ route('licence-types.edit', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $item->name }}</span></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 space-y-2 text-center">
                            <p>{{ __('No licence types at the moment') }}</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>

        </div>
    </div>
</x-app-layout>
