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
                @can('changeLicence', $enrollment)
                    <form method="POST" action="{{ route('my-enrollments.licence.update', $enrollment) }}" class="space-y-3 rounded-md border border-amber-400 bg-amber-50 p-4">
                        @csrf
                        @method('PUT')
                        <p class="text-sm font-medium text-amber-900">{{ __('The licence request was rejected: choose another licence.') }}</p>
                        @if ($enrollment->licenceRequest?->rejection_reason)
                            <p class="text-sm text-amber-900">{{ $enrollment->licenceRequest->rejection_reason }}</p>
                        @endif
                        <x-validation-errors />
                        <fieldset class="space-y-2">
                            <legend class="sr-only">{{ __('Licence') }}</legend>
                            @foreach ($subjects as $item)
                                <label class="flex items-start gap-3">
                                    <input type="radio" name="subject" value="{{ $item['value'] }}" @checked(old('subject') === $item['value']) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                    <span class="text-sm text-zinc-900">{{ $item['label'] }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <x-button type="submit">{{ __('Use this licence') }}</x-button>
                    </form>
                @endcan

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
                        @elseif ($enrollment->licenceRequest?->status === \App\Enums\LicenceRequestStatus::Draft)
                            <a href="{{ route('licence-requests.edit', $enrollment->licenceRequest) }}" class="text-orange-600 hover:text-orange-900">{{ __('Licence request to complete') }}</a>
                        @elseif ($enrollment->needsLicence())
                            {{ __('The licence request was rejected') }}
                        @elseif ($enrollment->licenceRequest)
                            <a href="{{ route('licence-requests.show', $enrollment->licenceRequest) }}" class="text-orange-600 hover:text-orange-900">{{ __('Waiting for the licence request') }}</a>
                        @else
                            {{ __('Waiting for the licence request') }}
                        @endif
                    </dd>

                    @if ($enrollment->competitorLicence || $enrollment->requiresCompetitor() || $competitorLicences->isNotEmpty())
                        <dt class="text-sm text-zinc-500">{{ __('Competitor') }}</dt>
                        <dd class="sm:col-span-2 text-zinc-900 space-y-2">
                            @if ($enrollment->competitorLicence)
                                <p>{{ $enrollment->competitorLicence->holder->fullName() }} &middot; <span class="font-mono">{{ $enrollment->competitorLicence->number }}</span></p>
                            @elseif ($enrollment->requiresCompetitor())
                                <p class="text-amber-800">{{ __('The driver is underage: name the competitor before the organizer confirms.') }}</p>
                            @endif
                            @can('changeCompetitor', $enrollment)
                                @if ($competitorLicences->isNotEmpty())
                                    <form method="POST" action="{{ route('my-enrollments.competitor.update', $enrollment) }}" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        @method('PUT')
                                        <select name="competitor_licence" aria-label="{{ __('Competitor') }}" class="rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                            <option value="">{{ __('No competitor') }}</option>
                                            @foreach ($competitorLicences as $competitor)
                                                <option value="{{ $competitor->ulid }}" @selected($enrollment->competitor_licence_id === $competitor->getKey())>{{ $competitor->holder->fullName() }} &middot; {{ $competitor->number }}</option>
                                            @endforeach
                                        </select>
                                        <x-secondary-button type="submit">{{ __('Save competitor') }}</x-secondary-button>
                                    </form>
                                    <x-input-error for="competitor_licence" />
                                @elseif ($enrollment->requiresCompetitor())
                                    <p class="text-sm text-zinc-600">{{ __('Add the competitor\'s licence to your account first.') }} <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('My licences') }}</a></p>
                                @endif
                            @endcan
                        </dd>
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
                            @elseif ($enrollment->status === \App\Enums\EnrollmentStatus::Submitted && $enrollment->hasConfirmedRaceNumber())
                                <span class="text-sm text-zinc-600">{{ __('Confirmed by the organizer: ask them to change it.') }}</span>
                            @endcan
                        </dd>
                    @endif
                </dl>

                @if ($enrollment->option)
                    <section class="space-y-3">
                        <h3 class="font-semibold text-zinc-900">{{ $enrollment->option->title }} &middot; <span class="font-mono tabular-nums">{{ $enrollment->amount === 0 ? __('Free') : $enrollment->currency->format($enrollment->amount) }}</span> &middot; {{ $enrollment->payment_status->label() }}</h3>
                        @include('partials.enrollment-payment-records', ['enrollment' => $enrollment])
                        @if ($options->count() > 1)
                            <form method="POST" action="{{ route('my-enrollments.option.update', $enrollment) }}" class="flex flex-wrap items-end gap-2">
                                @csrf
                                @method('PUT')
                                <div>
                                    <x-label for="option" value="{{ __('Switch to') }}" />
                                    <select id="option" name="option" class="mt-1 rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                        @foreach ($options as $item)
                                            <option value="{{ $item->ulid }}" @selected($enrollment->enrollment_option_id === $item->getKey())>{{ $item->title }} &middot; {{ $item->amountLabel() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-secondary-button type="submit">{{ __('Change option') }}</x-secondary-button>
                            </form>
                            <p class="text-sm text-zinc-600">{{ __('You can change option until the enrollment is paid in full and enrollment closes: what you already paid counts towards the new one.') }}</p>
                            <x-input-error for="option" />
                        @endif
                        @if ($enrollment->status->isActive())
                            @can('pay', $enrollment)
                                <div class="prose prose-zinc">
                                    @include('partials.enrollment-payment', ['enrollment' => $enrollment])
                                </div>
                            @endcan
                        @elseif ($enrollment->payment_status === \App\Enums\EnrollmentPaymentStatus::PaymentSubmitted || $enrollment->paidAmount() > 0)
                            <p class="text-sm text-zinc-700">{{ __('For a refund, email the organizer.') }}</p>
                        @endif
                    </section>
                @endif

                @if ($enrollment->status === \App\Enums\EnrollmentStatus::Submitted && $enrollment->hasPaymentUnderReview())
                    <p class="text-sm text-zinc-600">{{ __('You can withdraw once the organizer reviews your payment proof.') }}</p>
                @endif

                @can('withdraw', $enrollment)
                    <form method="POST" action="{{ route('my-enrollments.withdraw', $enrollment) }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Withdraw') }}</x-secondary-button>
                    </form>
                @endcan
            </div>

        </div>
    </div>
</x-app-layout>
