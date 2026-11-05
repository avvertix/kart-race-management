<x-app-layout>
    <x-slot name="title">
        {{ __('Enrollment') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex flex-wrap gap-2">
            <span><a href="{{ route('public.championships.enrollment', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ $enrollment->holder()?->fullName() }}</span>
        </h2>
        <p class="text-sm text-zinc-500">{{ __('Enrollment') }} &middot; {{ $enrollment->status->label() }}</p>
    </x-slot>

    @php
        $heldReservations = $enrollment->reservations->filter(fn ($reservation) => $reservation->status->holdsTheNumber());
    @endphp

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                @if ($enrollment->rejection_reason)
                    <p class="rounded-md border border-amber-400 bg-amber-50 p-3 text-sm text-amber-900">{{ $enrollment->rejection_reason }}</p>
                @endif

                <dl class="grid gap-x-4 gap-y-3 rounded-md border border-zinc-200 bg-white p-4 sm:grid-cols-3">
                    <dt class="text-sm text-zinc-500">{{ __('Driver') }}</dt>
                    <dd class="sm:col-span-2 text-zinc-900">{{ $enrollment->holder()?->fullName() }}</dd>

                    <dt class="text-sm text-zinc-500">{{ __('Licence') }}</dt>
                    <dd class="sm:col-span-2 text-zinc-900">
                        @if ($enrollment->licence)
                            {{ $enrollment->licence->licenceTypeLabel() }} &middot; <span class="font-mono">{{ $enrollment->licence->number }}</span>
                        @elseif ($enrollment->licenceRequest)
                            <a href="{{ route('licence-requests.show', $enrollment->licenceRequest) }}" class="text-orange-600 hover:text-orange-900">{{ __('Waiting for the licence request') }}</a>
                        @else
                            {{ __('Waiting for the licence request') }}
                        @endif
                    </dd>

                    @if ($enrollment->competitorLicence)
                        <dt class="text-sm text-zinc-500">{{ __('Competitor') }}</dt>
                        <dd class="sm:col-span-2 text-zinc-900">{{ $enrollment->competitorLicence->holder->fullName() }} &middot; <span class="font-mono">{{ $enrollment->competitorLicence->number }}</span></dd>
                    @endif

                    @if ($heldReservations->isNotEmpty() || $enrollment->grantsBibReservation())
                        <dt class="text-sm text-zinc-500">{{ __('Race number') }}</dt>
                        <dd class="sm:col-span-2 flex flex-wrap items-center gap-3 text-zinc-900">
                            @forelse ($heldReservations as $reservation)
                                <span><span class="font-mono">{{ $reservation->bib }}</span> &middot; {{ $reservation->status->label() }}</span>
                            @empty
                                <span class="text-zinc-600">{{ __('Not picked yet') }}</span>
                            @endforelse
                            @can('pickRaceNumber', $enrollment)
                                <a href="{{ route('my-enrollments.race-number.edit', $enrollment) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ $heldReservations->isNotEmpty() ? __('Change race number') : __('Pick a race number') }}</a>
                            @endcan
                        </dd>
                    @endif
                </dl>

                @if ($enrollment->order && $enrollment->status->isActive())
                    <section class="space-y-3">
                        <h3 class="font-semibold text-zinc-900">{{ __('Payment') }} &middot; {{ $enrollment->order->status->label() }}</h3>
                        @can('pay', $enrollment->order)
                            <div class="prose prose-zinc">
                                @include('partials.order-payment', ['order' => $enrollment->order, 'paymentAction' => route('my-enrollments.payment.store', $enrollment)])
                            </div>
                        @endcan
                    </section>
                @endif

                @if ($enrollment->order && ! $enrollment->status->isActive())
                    <p class="text-sm text-zinc-700">
                        {{ __('Payment') }}: {{ $enrollment->order->status->label() }}
                        @if (in_array($enrollment->order->status, [\App\Enums\OrderStatus::Paid, \App\Enums\OrderStatus::PaymentSubmitted], true))
                            &middot; {{ __('For a refund, email the organizer.') }}
                        @endif
                    </p>
                @endif

                @can('withdraw', $enrollment)
                    <form method="POST" action="{{ route('my-enrollments.withdraw', $enrollment) }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Withdraw') }}</x-secondary-button>
                    </form>
                @endcan
            </div>

            @if ($enrollment->order)
                <aside class="rounded-md border border-zinc-200 bg-white p-4 space-y-2 self-start">
                    @foreach ($enrollment->order->lines as $line)
                        <div class="flex justify-between gap-4">
                            <span>{{ $line->description }}</span>
                            <span class="font-mono tabular-nums">{{ $line->amount === null ? $line->price->label() : $enrollment->order->currency->format($line->amount) }}</span>
                        </div>
                    @endforeach

                    <div class="flex justify-between gap-4 border-t border-zinc-200 pt-2 font-semibold">
                        <span>{{ __('Total') }}</span>
                        <span class="font-mono tabular-nums">{{ $enrollment->order->total === null ? __('to be set') : $enrollment->order->currency->format($enrollment->order->total) }}</span>
                    </div>
                </aside>
            @endif
        </div>
    </div>
</x-app-layout>
