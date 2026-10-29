@php
    $selectClass = 'rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<x-app-layout>
    <x-slot name="title">
        {{ __('All licences') }}
    </x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('All licences') }}
        </h2>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">

            <form method="GET" action="{{ route('licences.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <x-label for="provider" value="{{ __('Provider') }}" />
                    <select id="provider" name="provider" class="mt-1 {{ $selectClass }}">
                        <option value="">{{ __('All') }}</option>
                        @foreach (\App\Enums\LicenceProvider::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['provider'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="year" value="{{ __('Year') }}" />
                    <select id="year" name="year" class="mt-1 {{ $selectClass }}">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected((int) ($filters['year'] ?? 0) === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <label for="unverified" class="flex items-center gap-2 pb-2 text-sm">
                    <x-checkbox id="unverified" name="unverified" value="1" :checked="(bool) ($filters['unverified'] ?? false)" />
                    {{ __('Declared, to be verified') }}
                </label>
                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            </form>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Holder') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Licence') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Year') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Managed by') }}</th>
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
                            <p>{{ $item->provider->label() }} &middot; {{ $item->licenceTypeLabel() }}</p>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->validity_year }}</td>
                        <td class="px-3 py-4 text-sm text-zinc-500">{{ $item->user?->name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">@include('my-licence.partials.status', ['licence' => $item])</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <a href="{{ route('licences.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}<span class="sr-only">, {{ $item->number }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 space-y-2 text-center">
                            <p>{{ __('No licences at the moment') }}</p>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            {{ $licences->links() }}

        </div>
    </div>
</x-app-layout>
