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
                                    <li class="relative flex flex-wrap items-center justify-between gap-3 p-4 hover:bg-orange-50">
                                        <div>
                                            <a href="{{ route('my-enrollments.show', $item) }}" class="font-medium text-zinc-900">
                                                <span class="absolute inset-0"></span>{{ $item->holder()?->fullName() }}
                                            </a>
                                            <p class="text-sm text-zinc-600">
                                                {{ $item->licence ? $item->licence->licenceTypeLabel() : __('Waiting for the licence request') }}
                                                @foreach ($item->reservations->filter(fn ($reservation) => $reservation->status->holdsTheNumber()) as $reservation)
                                                    &middot; {{ __('Race number') }} <span class="font-mono">{{ $reservation->bib }}</span>
                                                @endforeach
                                            </p>
                                        </div>
                                        <p class="text-sm text-zinc-700">
                                            {{ $item->status->label() }}
                                            @if ($item->order && $item->status->isActive() && $item->order->status !== \App\Enums\OrderStatus::Paid)
                                                &middot; {{ __('Payment') }}: {{ $item->order->status->label() }}
                                            @endif
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($isOpen)
                        @php
                            $paysByDefault = (bool) ($paymentOptions->firstWhere('value', old('product', ''))['pays'] ?? ! $settings->isFree());
                        @endphp
                        <form method="POST" action="{{ route('public.championships.enrollment.store', $championship) }}" class="space-y-6 rounded-md border border-zinc-200 bg-white p-4"
                            x-data="{
                                subjects: {{ Js::from($subjects) }},
                                paymentOptions: {{ Js::from($paymentOptions) }},
                                subject: {{ Js::from(old('subject', '')) }},
                                product: {{ Js::from(old('product', '')) }},
                                get chosen() { return this.subjects.find((item) => item.value === this.subject) ?? null },
                                get payment() { return this.paymentOptions.find((item) => item.value === this.product) ?? this.paymentOptions[0] },
                                get needsCompetitor() { return this.chosen !== null && this.chosen.underage },
                                get showsNumber() { return this.chosen !== null && this.payment.grantsNumber },
                            }">
                            @csrf
                            <h3 class="font-semibold text-zinc-900">{{ __('Enroll a licence') }}</h3>

                            <x-validation-errors />

                            @if ($subjects->isEmpty())
                                <p class="text-sm text-zinc-600">
                                    {{ __('You have no driver licence valid for :year that is not enrolled yet.', ['year' => $championship->start_at->year]) }}
                                    <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('Add or request a licence') }}</a>
                                </p>
                            @else
                                <fieldset class="space-y-2">
                                    <legend class="text-sm font-medium text-zinc-900">{{ __('Licence') }}</legend>
                                    @foreach ($subjects as $item)
                                        <label class="flex items-start gap-3">
                                            <input type="radio" name="subject" value="{{ $item['value'] }}" x-model="subject" @checked(old('subject') === $item['value']) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                            <span class="text-sm text-zinc-900">{{ $item['label'] }}</span>
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
                                        <p class="mt-1 text-sm text-zinc-600" x-show="needsCompetitor" x-cloak>{{ __('Required for underage drivers.') }}</p>
                                    </div>
                                @elseif ($subjects->contains('underage', true))
                                    <p class="text-sm text-zinc-600" x-show="needsCompetitor" x-cloak>{{ __('The driver is underage: add the competitor\'s licence to your account first.') }} <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('My licences') }}</a></p>
                                @endif

                                @if ($paymentOptions->count() > 1)
                                    <fieldset class="space-y-2">
                                        <legend class="text-sm font-medium text-zinc-900">{{ __('What you are buying') }}</legend>
                                        @foreach ($paymentOptions as $option)
                                            <label class="flex items-start gap-3">
                                                <input type="radio" name="product" value="{{ $option['value'] }}" x-model="product" @checked(old('product', '') === $option['value']) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                                                <span class="text-sm text-zinc-900">{{ $option['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                @endif

                                @if ($paymentOptions->contains('grantsNumber', true))
                                    <div x-show="showsNumber" x-cloak class="space-y-2">
                                        <template x-if="chosen && chosen.heldBib">
                                            <p class="rounded-md border border-lime-300 bg-lime-50 p-2 text-sm text-lime-900" x-text="{{ Js::from(__('This licence already holds race number :bib. No need to pick another one.')) }}.replace(':bib', chosen.heldBib)"></p>
                                        </template>
                                        <div x-show="!chosen?.heldBib">
                                            <livewire:race-number-picker :championship="$championship" :show-held="false" />
                                        </div>
                                    </div>
                                @endif

                                <div class="space-y-2 border-t border-zinc-200 pt-4" x-show="chosen" x-cloak aria-live="polite">
                                    <p class="text-sm font-medium text-zinc-900">{{ __('Review') }}</p>
                                    <p class="text-sm text-zinc-700" x-text="chosen ? chosen.label : ''"></p>
                                    <p class="text-sm text-zinc-700" x-text="payment.label"></p>
                                </div>

                                <p class="text-sm text-zinc-600" x-show="!chosen">{{ __('Choose the licence to enroll.') }}</p>

                                <x-button type="submit" x-bind:disabled="!chosen">
                                    <span x-text="payment.pays ? {{ Js::from(__('Submit and pay')) }} : {{ Js::from(__('Submit enrollment')) }}">{{ $paysByDefault ? __('Submit and pay') : __('Submit enrollment') }}</span>
                                </x-button>
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
                        @if ($settings->fee_covers_licence_renewal)
                            <p class="text-sm text-zinc-600">{{ __('It also covers the renewal of your licence, when you enroll with a licence request.') }}</p>
                        @endif
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
