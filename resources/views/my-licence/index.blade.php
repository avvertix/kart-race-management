<x-app-layout>
    <x-slot name="title">
        {{ __('My licences') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                {{ __('My licences') }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <x-button-link href="{{ route('public.products.index') }}">
                    {{ __('Request a licence') }}
                </x-button-link>
                @can('create', \App\Models\Licence::class)
                    <x-secondary-button-link href="{{ route('my-licences.create') }}">
                        {{ __('Add a licence I already have') }}
                    </x-secondary-button-link>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">

            <p class="text-sm text-zinc-600 max-w-prose">{{ __('The licences of the people you act for: yourself, your children or the drivers of your team. Each licence holds its holder\'s details.') }}</p>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Holder') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Licence') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Year') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Open') }}</span>
                    </th>
                </x-slot>

                @forelse ($licences as $item)
                    <tr>
                        <td class="py-4 pl-4 pr-3 text-sm text-zinc-900 sm:pl-6 font-medium">{{ $item->holder->fullName() }}</td>
                        <td class="px-3 py-4 text-sm text-zinc-500">
                            <p class="text-zinc-900 font-mono">{{ $item->number }}</p>
                            <p>{{ $item->provider->label() }} &middot; {{ $item->licenceType?->name ?? $item->role->label() }} &middot; {{ $item->gradeName() }}</p>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->validity_year }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">@include('my-licence.partials.status', ['licence' => $item])</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <a href="{{ route('my-licences.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}<span class="sr-only">, {{ $item->number }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 space-y-2 text-center">
                            <p>{{ __('No licences yet. Request one, or add a licence you already have.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>

        </div>
    </div>
</x-app-layout>
