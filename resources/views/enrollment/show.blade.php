@php
    $championship = $enrollment->championship;
    $licence = $enrollment->licence;
@endphp

<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ $enrollment->holder()?->fullName() }} - {{ __('Enrollments') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        <x-button-link href="{{ route('championships.enrollments.index', [$championship, 'status' => $enrollment->status->value]) }}">
            {{ __('All enrollments') }}
        </x-button-link>
    </x-slot>

    <x-validation-errors class="mb-4" />

    <div class="grid gap-6 md:grid-cols-2">
        <div class="p-4 bg-white shadow-sm rounded-sm space-y-3">
            <p class="text-2xl font-bold">{{ $enrollment->holder()?->fullName() }}</p>
            <p>
                <span class="block text-zinc-500">{{ __('Status') }}</span>
                {{ $enrollment->status->label() }}
                @if ($enrollment->confirmed_at)
                    &middot; <x-time :value="$enrollment->confirmed_at" />
                @endif
            </p>
            @if ($enrollment->rejection_reason)
                <p>
                    <span class="block text-zinc-500">{{ __('Rejection reason') }}</span>
                    {{ $enrollment->rejection_reason }}
                </p>
            @endif
            <p>
                <span class="block text-zinc-500">{{ __('Licence') }}</span>
                @if ($licence)
                    {{ $licence->provider->label() }} &middot; {{ $licence->licenceTypeLabel() }} &middot; <span class="font-mono">{{ $licence->number }}</span> &middot; {{ $licence->validity_year }}
                    <span class="block text-sm text-zinc-600">{{ $licence->source->label() }}{{ $licence->isVerified() ? ' · '.__('Verified') : '' }}</span>
                @elseif ($enrollment->licenceRequest)
                    {{ __('Waiting for the licence request') }} &middot; {{ $enrollment->licenceRequest->status->label() }}
                @endif
            </p>
            <p>
                <span class="block text-zinc-500">{{ __('Competitor') }}</span>
                {{ $enrollment->competitorLicence ? $enrollment->competitorLicence->holder->fullName().' · '.$enrollment->competitorLicence->number : __('No competitor') }}
                @if ($enrollment->isDriverUnderage())
                    <span class="block text-sm text-zinc-600">{{ __('The driver is underage.') }}</span>
                @endif
            </p>
            <p>
                <span class="block text-zinc-500">{{ __('Payment') }}</span>
                @if ($enrollment->order)
                    <a href="{{ route('orders.show', $enrollment->order) }}" class="text-orange-600 hover:text-orange-900">{{ $enrollment->order->status->label() }}</a>
                @else
                    {{ __('No order') }}
                @endif
            </p>
            @if ($enrollment->reservations->isNotEmpty())
                <p>
                    <span class="block text-zinc-500">{{ __('Race number') }}</span>
                    @foreach ($enrollment->reservations as $reservation)
                        <span class="font-mono">{{ $reservation->bib }}</span> &middot; {{ $reservation->status->label() }}
                        @can('confirm', $reservation)
                            <form method="POST" action="{{ route('bib-reservations.confirm', $reservation) }}" class="inline">
                                @csrf
                                <input type="hidden" name="return" value="enrollment">
                                <button type="submit" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Confirm number :bib', ['bib' => $reservation->bib]) }}</button>
                            </form>
                        @endcan
                    @endforeach
                </p>
            @endif
            <div>
                <span class="block text-zinc-500">{{ __('Previous numbers') }}</span>
                @forelse ($previousNumbers as $item)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-100 py-2 first:border-t-0">
                        <p class="text-sm">
                            <span class="font-mono font-semibold">{{ $item['bib'] }}</span>
                            &middot; {{ $item['championship'] }}
                            &middot; {{ trans_choice(':count race|:count races', $item['races'], ['count' => $item['races']]) }}
                            <span class="block text-zinc-600">{{ $item['driver'] }} &middot; {{ collect($item['matches'])->map->label()->join(', ') }}</span>
                        </p>
                        @if ($canPickNumber && $availability->isFree($item['bib']))
                            <form method="POST" action="{{ route('enrollments.race-number.update', $enrollment) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="bib" value="{{ $item['bib'] }}">
                                <x-secondary-button type="submit">{{ __('Use number :bib', ['bib' => $item['bib']]) }}</x-secondary-button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-600">{{ __('No numbers found in past championships.') }}</p>
                @endforelse
            </div>
            <p>
                <span class="block text-zinc-500">{{ __('Submitted by') }}</span>
                {{ $enrollment->user->name }} &middot; <x-time :value="$enrollment->created_at" />
            </p>
        </div>

        <div class="space-y-6">
            @if ($enrollment->status === \App\Enums\EnrollmentStatus::Submitted)
                @if ($missing !== [])
                    <div class="rounded-md border border-yellow-300 bg-yellow-50 p-4 space-y-1" role="status">
                        <p class="font-semibold text-yellow-900">{{ __('Not ready to confirm') }}</p>
                        <ul class="list-disc pl-5 text-sm text-yellow-900">
                            @foreach ($missing as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    @if ($licence && ! $licence->isVerifiedOrEmitted())
                        @can('verify', $licence)
                            <form method="POST" action="{{ route('enrollments.verify-licence', $enrollment) }}">
                                @csrf
                                <x-secondary-button type="submit">{{ __('Mark licence as verified') }}</x-secondary-button>
                            </form>
                        @endcan
                    @endif

                    @if ($missing === [])
                        <form method="POST" action="{{ route('enrollments.confirm', $enrollment) }}">
                            @csrf
                            <x-button type="submit">{{ __('Confirm enrollment') }}</x-button>
                        </form>
                    @endif
                </div>

                @if ($championship->enrollment->grants_bib_reservation)
                    <form method="POST" action="{{ route('enrollments.race-number.update', $enrollment) }}" class="space-y-3 rounded-md border border-zinc-200 bg-white p-4">
                        @csrf
                        @method('PUT')
                        <livewire:race-number-picker :championship="$championship" :enrollment="$enrollment" :show-holders="true" />
                        <x-secondary-button type="submit">{{ __('Save race number') }}</x-secondary-button>
                    </form>
                @endif

                <form method="POST" action="{{ route('enrollments.reject', $enrollment) }}" class="space-y-2">
                    @csrf
                    <x-label for="reason" value="{{ __('Rejection reason') }}" />
                    <x-textarea id="reason" name="reason" rows="3" class="block w-full" required>{{ old('reason') }}</x-textarea>
                    <x-input-error for="reason" class="mt-2" />
                    <x-danger-button type="submit">{{ __('Reject enrollment') }}</x-danger-button>
                </form>
            @endif

            @if ($enrollment->status->isActive())
                <form method="POST" action="{{ route('enrollments.withdraw', $enrollment) }}" class="space-y-2">
                    @csrf
                    <p class="text-sm text-zinc-600">{{ __('Withdrawing releases the race number and cancels an unpaid order. Refunds are settled with the driver.') }}</p>
                    <x-danger-button type="submit">{{ __('Withdraw enrollment') }}</x-danger-button>
                </form>
            @endif
        </div>
    </div>
</x-championship-page-layout>
