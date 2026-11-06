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
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Competitor') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Race number') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Submitted') }}</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('Payment') }}</th>
                @if ($status === \App\Enums\EnrollmentStatus::Submitted)
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">{{ __('To confirm') }}</th>
                @endif
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
                            <span class="block">{{ $item->licence->isVerifiedOrEmitted() ? $item->licence->source->label().' · '.__('Verified') : __('Not verified yet') }}</span>
                        @elseif ($item->licenceRequest)
                            {{ __('Waiting for the licence request') }}
                            <span class="block">{{ $item->licenceRequest->status->label() }}</span>
                        @else
                            {{ __('Waiting for the licence request') }}
                        @endif
                    </td>
                    <td class="px-3 py-4 text-sm text-zinc-500">{{ $item->competitorLicence?->holder->fullName() ?? '—' }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">
                        @forelse ($item->reservations->filter(fn ($reservation) => $reservation->status->holdsTheNumber()) as $reservation)
                            <span class="block"><span class="font-mono">{{ $reservation->bib }}</span> &middot; {{ $reservation->status->label() }}</span>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500"><x-time :value="$item->created_at" /></td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->payment_status->label() }}</td>
                    @if ($status === \App\Enums\EnrollmentStatus::Submitted)
                        @php($missing = $item->missingForConfirmation())
                        <td class="px-3 py-4 text-sm">
                            @if ($missing === [])
                                <span class="text-lime-700">{{ __('Ready to confirm') }}</span>
                            @else
                                <ul class="text-amber-800">
                                    @foreach ($missing as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    @endif
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('enrollments.show', $item) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}<span class="sr-only">, {{ $item->holder()?->fullName() }}</span></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $status === \App\Enums\EnrollmentStatus::Submitted ? 8 : 7 }}" class="px-3 py-4 text-center">{{ __('No enrollments.') }}</td>
                </tr>
            @endforelse
        </x-table>

        {{ $enrollments->links() }}
    </div>
</x-championship-page-layout>
