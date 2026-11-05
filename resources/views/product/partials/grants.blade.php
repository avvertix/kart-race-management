@php
    $selectClass = 'mt-1 block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('What the package grants') }}</x-slot>
        <x-slot name="description">
            {{ __('Everything is granted when you confirm the payment. What needs a licence, the enrollment and the race fees, is granted once the licence is emitted.') }}
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5 space-y-4">
            <ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200 bg-white">
                @forelse ($product->grants as $grant)
                    <li class="flex flex-wrap items-center justify-between gap-3 p-3">
                        <span class="text-sm text-zinc-900">{{ $grant->summary() }}</span>
                        <form method="POST" action="{{ route('product-grants.destroy', $grant) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Remove') }}</button>
                        </form>
                    </li>
                @empty
                    <li class="p-3 text-sm text-zinc-600">{{ __('The package grants nothing yet.') }}</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('products.grants.store', $product) }}" class="grid gap-4 sm:grid-cols-2" x-data="{ kind: '{{ old('kind', \App\Enums\ProductGrantKind::Entitlement->value) }}' }">
                @csrf
                <div>
                    <x-label for="grant_kind" value="{{ __('Grant') }}" />
                    <select id="grant_kind" name="kind" x-model="kind" class="{{ $selectClass }}">
                        @foreach (\App\Enums\ProductGrantKind::cases() as $kind)
                            <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="kind" class="mt-2" />
                </div>

                <div x-show="kind !== '{{ \App\Enums\ProductGrantKind::LicenceEmission->value }}'">
                    <x-label for="grant_championship" value="{{ __('Championship') }}" />
                    <select id="grant_championship" name="championship" class="{{ $selectClass }}">
                        @foreach ($championships as $championship)
                            <option value="{{ $championship->uuid }}" @selected(old('championship') === $championship->uuid)>{{ $championship->title }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="championship" class="mt-2" />
                </div>

                <div x-show="kind === '{{ \App\Enums\ProductGrantKind::Entitlement->value }}'">
                    <x-label for="grant_mode" value="{{ __('What it gives') }}" />
                    <select id="grant_mode" name="mode" class="{{ $selectClass }}">
                        @foreach (\App\Enums\EntitlementMode::cases() as $mode)
                            <option value="{{ $mode->value }}" @selected(old('mode') === $mode->value)>{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="mode" class="mt-2" />
                </div>

                <div x-show="kind === '{{ \App\Enums\ProductGrantKind::Entitlement->value }}'">
                    <x-label for="grant_amount" value="{{ __('Amount') }}" />
                    <p class="text-sm text-zinc-600">{{ __('The number of race fees, or the balance in euros.') }}</p>
                    <x-input id="grant_amount" type="text" inputmode="decimal" name="amount" class="mt-1 block w-40" :value="old('amount')" />
                    <x-input-error for="amount" class="mt-2" />
                </div>

                <label class="flex items-start gap-3 sm:col-span-2" x-show="kind === '{{ \App\Enums\ProductGrantKind::Enrollment->value }}'">
                    <x-checkbox name="grants_bib_reservation" value="1" :checked="old('grants_bib_reservation')" />
                    <span class="text-sm text-zinc-900">{{ __('The driver reserves a race number with the enrollment') }}</span>
                </label>

                <div class="sm:col-span-2">
                    <x-secondary-button type="submit">{{ __('Add grant') }}</x-secondary-button>
                </div>
            </form>
        </div>
    </div>
</div>
