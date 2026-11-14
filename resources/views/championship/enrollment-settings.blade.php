<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Enrollment') }} - {{ $championship->title }}
    </x-slot>

    <x-validation-errors class="mb-4" />

    @php
        $settings = $championship->enrollment;
        $timezone = config('races.timezone');
        $localInput = fn (?\Carbon\CarbonInterface $value) => $value?->setTimezone($timezone)->format('Y-m-d\TH:i');
    @endphp

    <form method="POST" action="{{ route('championships.enrollment-settings.update', $championship) }}">
        @csrf
        @method('PUT')

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Enrollment') }}</x-slot>
                <x-slot name="description">
                    {{ __('Let drivers enroll in the championship in a given period. Registering for single races stays possible either way.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 space-y-4">
                    <label class="flex items-start gap-3">
                        <x-checkbox name="enabled" value="1" :checked="old('enabled', $settings->enabled)" />
                        <span class="text-sm font-medium text-zinc-900">{{ __('Drivers can enroll') }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-label for="opens_at" value="{{ __('Opens') }}" />
                            <x-input id="opens_at" type="datetime-local" name="opens_at" class="mt-1 block w-full" :value="old('opens_at', $localInput($settings->opens_at))" />
                            <x-input-error for="opens_at" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="closes_at" value="{{ __('Closes') }}" />
                            <x-input id="closes_at" type="datetime-local" name="closes_at" class="mt-1 block w-full" :value="old('closes_at', $localInput($settings->closes_at))" />
                            <x-input-error for="closes_at" class="mt-2" />
                        </div>
                    </div>
                    <p class="text-sm text-zinc-500">{{ __('Times are in the :timezone timezone. Both are required to let drivers enroll.', ['timezone' => $timezone]) }}</p>
                </div>
            </div>
        </div>

        <x-section-border />

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Rules') }}</x-slot>
                <x-slot name="description">
                    {{ __('What enrolling gives and asks for.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 space-y-3">
                    <label class="flex items-start gap-3">
                        <x-checkbox name="requires_competitor_when_underage" value="1" :checked="old('requires_competitor_when_underage', $settings->requires_competitor_when_underage)" />
                        <div>
                            <span class="text-sm font-medium text-zinc-900">{{ __('Competitor required when underage') }}</span>
                            <p class="text-sm text-zinc-500">{{ __('An underage driver must name a competitor licence to enroll.') }}</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="md:grid md:grid-cols-3 md:gap-6 mt-6">
            <div></div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 flex flex-wrap items-center gap-4">
                    <x-button type="submit">
                        {{ __('Save') }}
                    </x-button>
                    @if ($settings->enabled)
                        <a href="{{ route('public.championships.enrollment', $championship) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Open the public page') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <x-section-border />

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Enrollment options') }}</x-slot>
            <x-slot name="description">
                {{ __('What drivers enroll with: free or a fixed amount, covering the licence renewal, reserving a race number or giving race fee discounts, possibly only for some licence categories.') }}
            </x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2 px-4 py-5 space-y-3">
            @forelse ($options as $option)
                <div class="flex flex-wrap items-start justify-between gap-3 rounded-md border border-zinc-200 bg-white p-3">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">
                            {{ $option->title }} &middot; <span class="font-mono tabular-nums">{{ $option->amountLabel() }}</span>
                            @unless ($option->active)
                                &middot; <span class="text-zinc-500">{{ __('Not offered') }}</span>
                            @endunless
                        </p>
                        <p class="text-sm text-zinc-500">
                            {{ collect([
                                $option->paymentTermsLabel(),
                                $option->covers_licence_renewal ? __('Covers the licence renewal') : null,
                                $option->reserves_race_number ? __('Reserves a race number') : null,
                                $option->entitlement_mode?->describe((int) $option->entitlement_amount),
                                $option->licenceCategories() === [] ? null : collect($option->licenceCategories())->map->localizedName()->join(', '),
                            ])->filter()->join(' · ') }}
                        </p>
                    </div>
                    <a href="{{ route('enrollment-options.edit', $option) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Edit') }}<span class="sr-only">, {{ $option->title }}</span></a>
                </div>
            @empty
                <p class="text-sm text-zinc-500">{{ __('No enrollment options yet.') }}</p>
            @endforelse

            <x-button-link href="{{ route('championships.enrollment-options.create', $championship) }}">{{ __('Add an option') }}</x-button-link>
        </div>
    </div>
</x-championship-page-layout>
