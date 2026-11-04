@php
    $selectClass = 'rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Enrollments') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('review', \App\Models\Enrollment::class)
            <x-button-link href="{{ route('championships.enrollments.create', $championship) }}">
                {{ __('Enroll a licence') }}
            </x-button-link>
        @endcan
    </x-slot>

    <div class="space-y-4">
        <form method="GET" action="{{ route('championships.enrollments.index', $championship) }}" class="flex flex-wrap items-end gap-3">
            <div>
                <x-label for="status" value="{{ __('Status') }}" />
                <select id="status" name="status" class="mt-1 {{ $selectClass }}">
                    @foreach (\App\Enums\EnrollmentStatus::cases() as $item)
                        <option value="{{ $item->value }}" @selected($status === $item)>{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
        </form>

        <x-table>
            <x-slot name="head">
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">{{ __('Driver') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Licence') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Submitted') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Payment') }}</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">{{ __('Open') }}</span>
                </th>
            </x-slot>

            @forelse ($enrollments as $item)
                <tr>
                    <td class="py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6">{{ $item->holder()?->fullName() }}</td>
                    <td class="px-3 py-4 text-sm text-zinc-500">
                        @if ($item->licence)
                            {{ $item->licence->licenceTypeLabel() }} &middot; <span class="font-mono">{{ $item->licence->number }}</span>
                        @else
                            {{ __('Waiting for the licence request') }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500"><x-time :value="$item->created_at" /></td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->order?->status->label() ?? __('Free') }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('enrollments.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}<span class="sr-only">, {{ $item->holder()?->fullName() }}</span></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-center">{{ __('No enrollments.') }}</td>
                </tr>
            @endforelse
        </x-table>

        {{ $enrollments->links() }}
    </div>
</x-championship-page-layout>
