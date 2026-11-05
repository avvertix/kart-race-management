<x-app-layout>
    <x-slot name="title">
        {{ __('Enroll in :championship', ['championship' => $championship->title]) }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">{{ $championship->title }}</h2>
        <p class="text-sm text-zinc-500">{{ __('Championship enrollment') }}</p>
    </x-slot>

    @php
        $timezone = config('races.timezone');
        $isOpen = $settings->isOpen();
        $formatDate = fn (\Carbon\CarbonInterface $value) => $value->setTimezone($timezone)->isoFormat('D MMMM YYYY HH:mm');
    @endphp

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                @if ($championship->description)
                    <p class="text-zinc-700 whitespace-pre-line">{{ $championship->description }}</p>
                @endif

                <section @class([
                    'rounded-md border p-4 space-y-1',
                    'border-lime-300 bg-lime-50' => $isOpen,
                    'border-zinc-200 bg-white' => ! $isOpen,
                ]) role="status">
                    @if ($isOpen)
                        <p class="font-semibold text-lime-900">{{ __('Enrollment is open') }}</p>
                        <p class="text-sm text-lime-900">{{ __('Until :date.', ['date' => $formatDate($settings->closes_at)]) }}</p>
                    @elseif ($settings->opensLater())
                        <p class="font-semibold text-zinc-900">{{ __('Enrollment opens on :date', ['date' => $formatDate($settings->opens_at)]) }}</p>
                        @if ($settings->closes_at)
                            <p class="text-sm text-zinc-600">{{ __('Until :date.', ['date' => $formatDate($settings->closes_at)]) }}</p>
                        @endif
                    @else
                        <p class="font-semibold text-zinc-900">{{ __('Enrollment is closed') }}</p>
                    @endif
                </section>

                <p class="text-sm text-zinc-600">{{ __('Enrollment is optional: you can always register for single races.') }}</p>

                @auth
                    @if ($myEnrollments->isNotEmpty())
                        <section class="space-y-2">
                            <h3 class="font-semibold text-zinc-900">{{ __('Your enrollments') }}</h3>
                            <ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200 bg-white">
                                @foreach ($myEnrollments as $item)
                                    <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                                        <div>
                                            <p class="font-medium text-zinc-900">{{ $item->holder()?->fullName() }}</p>
                                            <p class="text-sm text-zinc-600">
                                                @if ($item->licence)
                                                    {{ $item->licence->licenceTypeLabel() }} &middot; <span class="font-mono">{{ $item->licence->number }}</span>
                                                @else
                                                    {{ __('Waiting for the licence request') }}
                                                @endif
                                                &middot; {{ $item->status->label() }}
                                            </p>
                                            @foreach ($item->reservations->filter(fn ($reservation) => $reservation->status->holdsTheNumber()) as $reservation)
                                                <p class="text-sm text-zinc-600">{{ __('Race number') }} <span class="font-mono">{{ $reservation->bib }}</span> &middot; {{ $reservation->status->label() }}</p>
                                            @endforeach
                                            @if ($item->rejection_reason)
                                                <p class="text-sm text-zinc-600">{{ $item->rejection_reason }}</p>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            @if ($item->order && $item->status->isActive())
                                                <a href="{{ route('my-orders.show', $item->order) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Payment') }}: {{ $item->order->status->label() }}</a>
                                            @endif
                                            @can('pickRaceNumber', $item)
                                                <a href="{{ route('my-enrollments.race-number.edit', $item) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ $item->reservations->contains(fn ($reservation) => $reservation->status->holdsTheNumber()) ? __('Change race number') : __('Pick a race number') }}</a>
                                            @endcan
                                            @can('withdraw', $item)
                                                <form method="POST" action="{{ route('my-enrollments.withdraw', $item) }}">
                                                    @csrf
                                                    <x-secondary-button type="submit">{{ __('Withdraw') }}</x-secondary-button>
                                                </form>
                                            @endcan
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($isOpen)
                        <form method="POST" action="{{ route('public.championships.enrollment.store', $championship) }}" class="space-y-4 rounded-md border border-zinc-200 bg-white p-4">
                            @csrf
                            <h3 class="font-semibold text-zinc-900">{{ __('Enroll a licence') }}</h3>

                            <x-validation-errors />

                            @if ($licences->isEmpty() && $licenceRequests->isEmpty())
                                <p class="text-sm text-zinc-600">
                                    {{ __('You have no driver licence valid for :year.', ['year' => $championship->start_at->year]) }}
                                    <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('Add or request a licence') }}</a>
                                </p>
                            @else
                                <fieldset class="space-y-2">
                                    <legend class="text-sm font-medium text-zinc-900">{{ __('Licence') }}</legend>
                                    @foreach ($licences as $licence)
                                        <label class="flex items-start gap-3">
                                            <input type="radio" name="subject" value="licence:{{ $licence->ulid }}" @checked(old('subject') === 'licence:'.$licence->ulid) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                            <span class="text-sm text-zinc-900">{{ $licence->holder->fullName() }} &middot; {{ $licence->licenceTypeLabel() }} &middot; <span class="font-mono">{{ $licence->number }}</span></span>
                                        </label>
                                    @endforeach
                                    @foreach ($licenceRequests as $licenceRequest)
                                        <label class="flex items-start gap-3">
                                            <input type="radio" name="subject" value="licence_request:{{ $licenceRequest->ulid }}" @checked(old('subject') === 'licence_request:'.$licenceRequest->ulid) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                            <span class="text-sm text-zinc-900">{{ $licenceRequest->holder?->fullName() }} &middot; {{ $licenceRequest->licenceTypeLabel() }} &middot; {{ __('Licence request: the enrollment waits for the licence') }}</span>
                                        </label>
                                    @endforeach
                                </fieldset>

                                @if ($competitorLicences->isNotEmpty())
                                    <div>
                                        <x-label for="competitor_licence" value="{{ __('Competitor') }}" />
                                        <select id="competitor_licence" name="competitor_licence" class="mt-1 rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                            <option value="">{{ __('No competitor') }}</option>
                                            @foreach ($competitorLicences as $competitor)
                                                <option value="{{ $competitor->ulid }}" @selected(old('competitor_licence') === $competitor->ulid)>{{ $competitor->holder->fullName() }} &middot; {{ $competitor->number }}</option>
                                            @endforeach
                                        </select>
                                        @if ($settings->requires_competitor_when_underage)
                                            <p class="mt-1 text-sm text-zinc-600">{{ __('Required for underage drivers.') }}</p>
                                        @endif
                                    </div>
                                @endif

                                @if ($packages->isNotEmpty())
                                    <fieldset class="space-y-2">
                                        <legend class="text-sm font-medium text-zinc-900">{{ __('What you are buying') }}</legend>
                                        <label class="flex items-start gap-3">
                                            <input type="radio" name="product" value="" @checked(blank(old('product'))) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                            <span class="text-sm text-zinc-900">{{ __('The enrollment on its own') }} &middot; {{ $settings->isFree() ? __('Free') : $price?->label() }}</span>
                                        </label>
                                        @foreach ($packages as $package)
                                            <label class="flex items-start gap-3">
                                                <input type="radio" name="product" value="{{ $package->ulid }}" @checked(old('product') === $package->ulid) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                                <span class="text-sm text-zinc-900">{{ $package->title }} &middot; {{ $package->orderablePrice()?->label() }}</span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif

                                @php
                                    $packagesGrantingNumber = $packages->filter(fn ($package) => $package->grants->contains(fn ($grant) => $grant->kind === \App\Enums\ProductGrantKind::Enrollment && $grant->championship_id === $championship->getKey() && $grant->grants_bib_reservation));
                                @endphp
                                @if ($settings->grants_bib_reservation || $packagesGrantingNumber->isNotEmpty())
                                    @unless ($settings->grants_bib_reservation)
                                        <p class="text-sm text-zinc-600">{{ __('The race number is reserved only with: :packages.', ['packages' => $packagesGrantingNumber->pluck('title')->join(', ')]) }}</p>
                                    @endunless
                                    <livewire:race-number-picker :championship="$championship" />
                                @endif

                                <x-button type="submit">{{ $settings->isFree() ? __('Submit enrollment') : __('Submit and pay') }}</x-button>
                            @endif
                        </form>
                    @endif
                @else
                    @if ($isOpen)
                        <p><a href="{{ route('login') }}" class="text-orange-600 hover:text-orange-900">{{ __('Sign in to enroll') }}</a></p>
                    @endif
                @endauth
            </div>

            <aside>
                <div class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                    <h3 class="text-sm text-zinc-500">{{ __('Price') }}</h3>
                    @if ($settings->isFree())
                        <p class="text-2xl">{{ __('Free') }}</p>
                    @elseif ($price === null)
                        <p class="text-zinc-700">{{ __('Not available at the moment') }}</p>
                    @else
                        <p class="text-2xl font-mono tabular-nums">{{ $price->label() }}</p>
                        @if ($price->isRange())
                            <p class="text-sm text-zinc-600">{{ __('The organizer confirms the final amount after reviewing your enrollment.') }}</p>
                        @endif
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
