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
                                            @if ($item->status->isActive() && ! $item->payment_status->isSettled())
                                                &middot; {{ __('Payment') }}: {{ $item->payment_status->label() }}
                                            @endif
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($isOpen)
                        @php
                            $paysByDefault = (bool) ($paymentOptions->firstWhere('value', old('option', ''))['pays'] ?? $paymentOptions->contains('pays', true));
                        @endphp
                        <form method="POST" action="{{ route('public.championships.enrollment.store', $championship) }}" class="space-y-6 rounded-md border border-zinc-200 bg-white p-4"
                            x-data="{
                                subjects: {{ Js::from($subjects) }},
                                paymentOptions: {{ Js::from($paymentOptions) }},
                                subject: {{ Js::from(old('subject', '')) }},
                                option: {{ Js::from(old('option', $paymentOptions->count() === 1 ? $paymentOptions->first()['value'] : '')) }},
                                get chosen() { return this.subjects.find((item) => item.value === this.subject) ?? null },
                                offered(item) { return item.categories.length === 0 || this.chosen?.category == null || item.categories.includes(this.chosen.category) },
                                get offeredOptions() { return this.paymentOptions.filter((item) => this.offered(item)) },
                                get payment() { return this.paymentOptions.find((item) => item.value === this.option && this.offered(item)) ?? (this.offeredOptions.length === 1 ? this.offeredOptions[0] : null) },
                                get needsCompetitor() { return this.chosen !== null && this.chosen.underage },
                                get showsNumber() { return this.chosen !== null && this.payment !== null && this.payment.grantsNumber },
                            }">
                            @csrf
                            @php
                                $year = $championship->start_at->year;
                                $card = 'flex min-h-11 cursor-pointer items-start gap-3 rounded-md border bg-white p-3 hover:border-orange-300 has-[:checked]:border-orange-400 has-[:checked]:bg-orange-50';
                                $radio = 'mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300';
                                $groups = [
                                    'ready' => __('Ready to enroll'),
                                    'waiting' => __('Waiting for the licence'),
                                    'renewal' => __('Renew and enroll'),
                                    'other' => null,
                                ];
                                $hasLicence = $subjects->contains(fn (array $item) => in_array($item['group'], ['ready', 'waiting'], true));
                            @endphp

                            <h3 class="font-semibold text-zinc-900">{{ __('Enroll a driver') }}</h3>

                            <x-validation-errors />

                            @if ($subjects->isEmpty())
                                <p class="text-sm text-zinc-600">
                                    {{ __('You have no driver licence valid for :year that is not enrolled yet.', ['year' => $year]) }}
                                    <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('Add or request a licence') }}</a>
                                </p>
                            @else
                                <fieldset class="space-y-3">
                                    <legend class="font-medium text-zinc-900">{{ __('Driver and licence') }}</legend>
                                    <p class="max-w-prose text-sm text-zinc-600">{{ __('Enrolling needs the driver\'s :year licence. Choose one in your account, or renew one here: the enrollment then waits until the licence is issued.', ['year' => $year]) }}</p>

                                    @unless ($hasLicence)
                                        <p class="max-w-prose rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ __('There is no :year licence in your account yet: choose the driver to renew below.', ['year' => $year]) }}</p>
                                    @endunless

                                    @foreach ($groups as $group => $title)
                                        @php($items = $subjects->where('group', $group))
                                        @continue($items->isEmpty())
                                        <div class="space-y-2">
                                            @if ($title)
                                                <p class="text-sm font-medium text-zinc-800">{{ $title }}</p>
                                            @endif
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                @foreach ($items as $item)
                                                    <label @class([$card, 'border-dashed border-zinc-300' => $group === 'other', 'border-zinc-200' => $group !== 'other'])>
                                                        <input type="radio" name="subject" value="{{ $item['value'] }}" x-model="subject" @checked(old('subject') === $item['value']) class="{{ $radio }}">
                                                        <span class="text-sm">
                                                            <span class="block font-medium text-zinc-900">{{ $item['name'] }}</span>
                                                            <span class="block text-zinc-600">{{ $item['detail'] }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    @if ($hasLicencesFromRaces)
                                        <p class="max-w-prose text-sm text-zinc-600">
                                            {{ __('Raced here before with a licence that is not listed? Add it to your account from your races, then renew it here.') }}
                                            <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('My licences') }}</a>
                                        </p>
                                    @endif

                                    <p class="max-w-prose rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700" x-show="chosen?.kind === 'renewal'" x-cloak>
                                        {{ __('After submitting, complete the licence request with the driver\'s details and documents. The organizer checks it, then confirms the enrollment once the licence is issued.') }}
                                    </p>
                                </fieldset>

                                @if ($competitorLicences->isNotEmpty())
                                    <div>
                                        <x-label for="competitor_licence" value="{{ __('Competitor') }}" />
                                        <select id="competitor_licence" name="competitor_licence" class="mt-1 rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                            <option value="">{{ __('No competitor') }}</option>
                                            @foreach ($competitorLicences as $competitor)
                                                <option value="{{ $competitor->ulid }}" @selected(old('competitor_licence') === $competitor->ulid) x-bind:hidden="subject === {{ Js::from('licence:'.$competitor->ulid) }}">{{ $competitor->holder->fullName() }} &middot; {{ $competitor->number }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-sm text-zinc-600" x-show="needsCompetitor && !chosen.parentCompetitor" x-cloak>{{ __('Required for underage drivers.') }}</p>
                                        <p class="mt-1 max-w-prose text-sm text-zinc-600" x-show="needsCompetitor && chosen.parentCompetitor" x-cloak>{{ __('Optional: the parent or guardian entered in the licence request acts as competitor.') }}</p>
                                    </div>
                                @elseif ($subjects->contains('underage', true))
                                    <p class="text-sm text-zinc-600" x-show="needsCompetitor && !chosen.parentCompetitor" x-cloak>{{ __('The driver is underage: add the competitor\'s licence to your account first.') }} <a href="{{ route('my-licences.index') }}" class="text-orange-600 hover:text-orange-900">{{ __('My licences') }}</a></p>
                                    <p class="max-w-prose text-sm text-zinc-600" x-show="needsCompetitor && chosen.parentCompetitor" x-cloak>{{ __('The driver is underage: the parent or guardian you enter in the licence request acts as competitor.') }}</p>
                                @endif

                                @if ($paymentOptions->count() > 1)
                                    <fieldset class="space-y-3">
                                        <legend class="font-medium text-zinc-900">{{ __('What you are buying') }}</legend>
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            @foreach ($paymentOptions as $option)
                                                <label class="{{ $card }} border-zinc-200" x-show="offered({{ Js::from($option) }})">
                                                    <input type="radio" name="option" value="{{ $option['value'] }}" x-model="option" x-bind:disabled="!offered({{ Js::from($option) }})" @checked(old('option', '') === $option['value']) class="{{ $radio }}">
                                                    <span class="text-sm">
                                                        <span class="block font-medium text-zinc-900">{{ $option['label'] }}</span>
                                                        @if ($option['coversRenewal'])
                                                            <span class="block text-lime-800" x-show="chosen?.kind === 'renewal'" x-cloak>{{ __('Licence renewal included') }}</span>
                                                        @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endif

                                <p class="max-w-prose text-sm text-zinc-700" x-show="chosen?.kind === 'renewal' && payment !== null" x-cloak
                                    x-text="payment?.coversRenewal ? {{ Js::from(__('The licence renewal is included in the option: nothing more to pay for it.')) }} : {{ Js::from($renewalPrice ? __('The licence renewal is not included: it is paid separately, :price.', ['price' => $renewalPrice]) : __('The licence renewal is not included: it is paid separately.')) }}"></p>

                                @if ($paymentOptions->contains('grantsNumber', true))
                                    <div x-show="showsNumber" x-cloak class="space-y-2">
                                        <template x-if="chosen && chosen.heldBib">
                                            <p class="rounded-md border border-lime-300 bg-lime-50 p-2 text-sm text-lime-900" x-text="{{ Js::from(__('This licence already holds race number :bib. No need to pick another one.')) }}.replace(':bib', chosen.heldBib)"></p>
                                        </template>
                                        <div x-show="!chosen?.heldBib">
                                            <x-race-number-picker :championship="$championship" :show-held="false" />
                                        </div>
                                    </div>
                                @endif

                                <div class="space-y-2 border-t border-zinc-200 pt-4" x-show="chosen" x-cloak aria-live="polite">
                                    <p class="text-sm font-medium text-zinc-900">{{ __('Review') }}</p>
                                    <p class="text-sm text-zinc-700" x-text="chosen ? chosen.label : ''"></p>
                                    <p class="text-sm text-zinc-700" x-text="payment ? payment.label : ''"></p>
                                </div>

                                <p class="text-sm text-zinc-600" x-show="!chosen || payment === null" x-text="!chosen ? {{ Js::from(__('Choose the licence to enroll.')) }} : {{ Js::from(__('Choose what you are buying.')) }}">{{ __('Choose the licence to enroll.') }}</p>

                                <input type="hidden" name="option" x-bind:value="payment ? payment.value : ''" x-bind:disabled="{{ $paymentOptions->count() > 1 ? 'true' : 'false' }}">

                                {{-- Disabled until complete: without loading="false", Flux shows its spinner on a disabled submit button. --}}
                                <x-button type="submit" :loading="false" x-bind:disabled="!chosen || payment === null">
                                    <span x-text="payment?.pays ? {{ Js::from(__('Submit and pay')) }} : {{ Js::from(__('Submit enrollment')) }}">{{ $paysByDefault ? __('Submit and pay') : __('Submit enrollment') }}</span>
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
                    <h3 class="text-sm text-zinc-500">{{ __('Enrollment options') }}</h3>
                    @forelse ($options as $option)
                        <div class="border-t border-zinc-100 pt-2 first-of-type:border-t-0 first-of-type:pt-0">
                            <p class="font-medium text-zinc-900">{{ $option->title }} &middot; <span class="font-mono tabular-nums">{{ $option->amountLabel() }}</span></p>
                            @if ($option->description)
                                <p class="text-sm text-zinc-600 whitespace-pre-line">{{ $option->description }}</p>
                            @endif
                            <p class="text-sm text-zinc-600">
                                {{ collect([
                                    $option->paymentTermsLabel(),
                                    $option->covers_licence_renewal ? __('Covers the licence renewal') : null,
                                    $option->reserves_race_number ? __('Reserves a race number') : null,
                                    $option->entitlement_mode?->describe((int) $option->entitlement_amount),
                                    $option->licenceCategories() === [] ? null : collect($option->licenceCategories())->map->localizedName()->join(', '),
                                ])->filter()->join(' · ') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-zinc-700">{{ __('Not available at the moment') }}</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
