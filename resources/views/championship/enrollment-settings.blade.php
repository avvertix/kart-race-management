<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Enrollment') }} - {{ $championship->title }}
    </x-slot>

    <x-validation-errors class="mb-4" />

    @php
        $settings = $championship->enrollment;
        $timezone = config('races.timezone');
        $localInput = fn (?\Carbon\CarbonInterface $value) => $value?->setTimezone($timezone)->format('Y-m-d\TH:i');
        $selectedProduct = old('product', $products->firstWhere('id', $settings->product_id)?->ulid ?? '');
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
                <x-slot name="title">{{ __('Price') }}</x-slot>
                <x-slot name="description">
                    {{ __('The enrollment fee, a fixed amount the same for every licence. Without a fee, enrolling is free.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 space-y-3">
                    <label class="flex items-start gap-3">
                        <input type="radio" name="product" value="" @checked($selectedProduct === '') class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                        <span class="text-sm font-medium text-zinc-900">{{ __('Free') }}</span>
                    </label>
                    @foreach ($products as $product)
                        <label class="flex items-start gap-3">
                            <input type="radio" name="product" value="{{ $product->ulid }}" @checked($selectedProduct === $product->ulid) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                            <div>
                                <span class="text-sm font-medium text-zinc-900">{{ $product->title }}</span>
                                <p class="text-sm text-zinc-500">{{ $product->currentPrice()?->label() ?? __('No current price') }}{{ $product->active ? '' : ' · '.__('Inactive') }}</p>
                            </div>
                        </label>
                    @endforeach
                    @if ($products->isEmpty())
                        <p class="text-sm text-zinc-500">
                            {{ __('No enrollment products for this championship yet.') }}
                            @can('create', \App\Models\Product::class)
                                <a href="{{ route('products.create') }}" class="underline hover:text-zinc-900">{{ __('Create one') }}</a>
                            @endcan
                        </p>
                    @endif
                    <x-input-error for="product" class="mt-2" />
                    <label class="flex items-start gap-3 pt-2">
                        <x-checkbox name="fee_covers_licence_renewal" value="1" :checked="old('fee_covers_licence_renewal', $settings->fee_covers_licence_renewal)" />
                        <div>
                            <span class="text-sm font-medium text-zinc-900">{{ __('The fee covers the licence renewal') }}</span>
                            <p class="text-sm text-zinc-500">{{ __('A driver enrolling with a licence request pays only the fee. A driver with a valid licence pays the same.') }}</p>
                        </div>
                    </label>
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
                        <x-checkbox name="grants_bib_reservation" value="1" :checked="old('grants_bib_reservation', $settings->grants_bib_reservation)" />
                        <div>
                            <span class="text-sm font-medium text-zinc-900">{{ __('Grants race number reservation') }}</span>
                            <p class="text-sm text-zinc-500">{{ __('Enrolling lets the driver reserve a race number for the whole championship.') }}</p>
                        </div>
                    </label>
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
</x-championship-page-layout>
