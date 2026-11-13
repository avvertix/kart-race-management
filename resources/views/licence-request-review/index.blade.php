@php
    $selectClass = 'rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<x-app-layout>
    <x-slot name="title">
        {{ __('Licence requests') }}
    </x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Licences and requests') }}
        </h2>
        <div class="mt-3">
            @include('licence.partials.tabs')
        </div>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">

            <form method="GET" action="{{ route('licence-request-reviews.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <x-label for="status" value="{{ __('Status') }}" />
                    <select id="status" name="status" class="mt-1 {{ $selectClass }}">
                        <option value="">{{ __('To process') }}</option>
                        @foreach (\App\Enums\LicenceRequestStatus::cases() as $item)
                            <option value="{{ $item->value }}" @selected($status === $item)>{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            </form>

            <x-table>
                <x-slot name="head">
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Holder') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Licence') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Submitted') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Documents') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Payment') }}</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Status') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('Open') }}</span>
                    </th>
                </x-slot>

                @forelse ($licenceRequests as $item)
                    <tr>
                        <td class="py-4 pl-4 pr-3 text-sm text-zinc-900 sm:pl-6 font-medium">{{ $item->holder?->fullName() ?? __('Holder not filled in yet') }}</td>
                        <td class="px-3 py-4 text-sm text-zinc-500">
                            <p class="text-zinc-900">{{ $item->provider?->label() }} &middot; {{ $item->licenceTypeLabel() ?? __('Licence not chosen yet') }}</p>
                            @if ($item->previous_licence_number)
                                <p class="font-mono">{{ $item->previous_licence_number }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            @if ($item->submitted_at)
                                <x-time :value="$item->submitted_at" />
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            @if ($item->documents_pruned_at)
                                {{ __('Documents deleted') }}
                            @else
                                {{ __(':reviewed of :total reviewed', [
                                    'reviewed' => $item->documents->where('status', '!==', \App\Enums\LicenceRequestDocumentStatus::Pending)->count(),
                                    'total' => $item->documents->count(),
                                ]) }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                            {{ $item->payment_status->label() }}
                            @if ($item->amountLabel())
                                <span class="block font-mono tabular-nums">{{ $item->amountLabel() }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->status->label() }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <a href="{{ route('licence-request-reviews.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}<span class="sr-only">, {{ $item->holder?->fullName() }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center">{{ __('No licence requests.') }}</td>
                    </tr>
                @endforelse
            </x-table>

            {{ $licenceRequests->links() }}
        </div>
    </div>
</x-app-layout>
