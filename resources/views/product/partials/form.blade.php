@php
    $product = $product ?? null;

    $timezone = config('races.timezone');

    $euro = fn (?int $cents) => $cents === null ? null : number_format($cents / 100, 2, '.', '');

    $prices = old('prices', $product?->prices->map(fn ($price) => [
        'id' => $price->getKey(),
        'type' => $price->isRange() ? 'range' : 'fixed',
        'amount' => $euro($price->amount),
        'amount_min' => $euro($price->amount_min),
        'amount_max' => $euro($price->amount_max),
        'starts_on' => $price->starts_at->setTimezone($timezone)->toDateString(),
        'ends_on' => $price->ends_at?->setTimezone($timezone)->toDateString(),
    ])->all() ?? []);

    $lockedPriceIds = $product?->prices->filter(fn ($price) => $price->order_lines_count > 0)->modelKeys() ?? [];

    $active = session()->hasOldInput() ? (bool) old('active') : ($product?->active ?? true);

    $inputClass = 'block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<div
    x-data="{
        kind: @js(old('kind', $product?->kind->value ?? \App\Enums\ProductKind::LicenceEmission->value)),
        prices: @js(array_values($prices)),
        locked: @js($lockedPriceIds),
        errors: @js($errors->getMessages()),
        error(key) { return (this.errors[key] ?? [])[0] ?? null },
        isLocked(price) { return price.id !== null && this.locked.includes(Number(price.id)) },
        addPrice() { this.prices.push({ id: null, type: 'fixed', amount: '', amount_min: '', amount_max: '', starts_on: '', ends_on: '' }) },
    }"
>

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Details') }}</x-slot>
            <x-slot name="description">{{ __('The kind cannot be changed once the product is created.') }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="kind" value="{{ __('Kind') }}*" />
                        @if ($product)
                            <p class="mt-1 text-sm">{{ $product->kind->label() }}</p>
                        @else
                            <select id="kind" name="kind" x-model="kind" class="mt-1 {{ $inputClass }}">
                                @foreach (\App\Enums\ProductKind::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                        <x-input-error for="kind" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="title" value="{{ __('Title') }}*" />
                        <x-input id="title" type="text" name="title" :value="old('title', $product?->title)" class="mt-1 block w-full" required />
                        <x-input-error for="title" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="description" value="{{ __('Description') }}" />
                        <x-textarea id="description" name="description" class="mt-1 block w-full">{{ old('description', $product?->description) }}</x-textarea>
                        <x-input-error for="description" class="mt-2" />
                    </div>
                    <template x-if="kind !== 'enrollment'">
                        <div class="col-span-6 sm:col-span-4">
                            <x-label for="licence_type" value="{{ __('Licence type') }}" />
                            <select id="licence_type" name="licence_type" class="mt-1 {{ $inputClass }}">
                                <option value="">{{ __('Choose a licence type') }}</option>
                                @foreach ($licenceTypes as $licenceType)
                                    <option value="{{ $licenceType->ulid }}" @selected(old('licence_type', $product?->licenceType?->ulid) === $licenceType->ulid)>{{ $licenceType->provider->label() }} · {{ $licenceType->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="licence_type" class="mt-2" />
                        </div>
                    </template>
                    <template x-if="kind !== 'licence_emission'">
                        <div class="col-span-6 sm:col-span-4">
                            <x-label for="championship" value="{{ __('Championship') }}" />
                            <select id="championship" name="championship" class="mt-1 {{ $inputClass }}">
                                <option value="">{{ __('Choose a championship') }}</option>
                                @foreach ($championships as $championship)
                                    <option value="{{ $championship->uuid }}" @selected(old('championship', $product?->championship?->uuid) === $championship->uuid)>{{ $championship->title }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="championship" class="mt-2" />
                        </div>
                    </template>
                    <div class="col-span-6 sm:col-span-4">
                        <label for="active" class="flex items-center">
                            <x-checkbox id="active" name="active" value="1" :checked="$active" />
                            <span class="ml-2">{{ __('List this product and accept orders') }}</span>
                        </label>
                        <p class="text-zinc-600 text-sm">{{ __('Existing orders are kept when the product is deactivated.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-section-border />

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Prices') }}</x-slot>
            <x-slot name="description">{{ __('A fixed amount, or a range when the final amount depends on the request. Dates are included. For an early-bird price add another price with earlier dates. Prices already used by orders only accept a new end date.') }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 space-y-4">
                <x-input-error for="prices" />

                <template x-for="(price, index) in prices" :key="index">
                    <div class="grid grid-cols-6 gap-2 rounded-md border border-zinc-200 p-3">
                        <input type="hidden" :name="`prices[${index}][id]`" :value="price.id">

                        <div class="col-span-6 sm:col-span-2">
                            <label class="text-sm font-medium text-zinc-700" :for="`price-type-${index}`">{{ __('Price') }}</label>
                            <select :id="`price-type-${index}`" :name="`prices[${index}][type]`" x-model="price.type" class="mt-1 {{ $inputClass }}" :class="isLocked(price) && 'pointer-events-none bg-zinc-100'">
                                <option value="fixed">{{ __('Fixed amount') }}</option>
                                <option value="range">{{ __('Range') }}</option>
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-4" x-show="price.type === 'fixed'">
                            <label class="text-sm font-medium text-zinc-700" :for="`price-amount-${index}`">{{ __('Amount (€)') }}*</label>
                            <input type="text" inputmode="decimal" :id="`price-amount-${index}`" :name="`prices[${index}][amount]`" x-model="price.amount" class="mt-1 {{ $inputClass }}" :disabled="price.type !== 'fixed'" :readonly="isLocked(price)" placeholder="180">
                        </div>
                        <div class="col-span-3 sm:col-span-2" x-show="price.type === 'range'">
                            <label class="text-sm font-medium text-zinc-700" :for="`price-min-${index}`">{{ __('From (€)') }}*</label>
                            <input type="text" inputmode="decimal" :id="`price-min-${index}`" :name="`prices[${index}][amount_min]`" x-model="price.amount_min" class="mt-1 {{ $inputClass }}" :disabled="price.type !== 'range'" :readonly="isLocked(price)" placeholder="230">
                        </div>
                        <div class="col-span-3 sm:col-span-2" x-show="price.type === 'range'">
                            <label class="text-sm font-medium text-zinc-700" :for="`price-max-${index}`">{{ __('To (€)') }}*</label>
                            <input type="text" inputmode="decimal" :id="`price-max-${index}`" :name="`prices[${index}][amount_max]`" x-model="price.amount_max" class="mt-1 {{ $inputClass }}" :disabled="price.type !== 'range'" :readonly="isLocked(price)" placeholder="310">
                        </div>
                        <div class="col-span-3 sm:col-span-2">
                            <label class="text-sm font-medium text-zinc-700" :for="`price-starts-${index}`">{{ __('Valid from') }}*</label>
                            <input type="date" :id="`price-starts-${index}`" :name="`prices[${index}][starts_on]`" x-model="price.starts_on" class="mt-1 {{ $inputClass }}" :readonly="isLocked(price)" required>
                        </div>
                        <div class="col-span-3 sm:col-span-2">
                            <label class="text-sm font-medium text-zinc-700" :for="`price-ends-${index}`">{{ __('Valid until') }}</label>
                            <input type="date" :id="`price-ends-${index}`" :name="`prices[${index}][ends_on]`" x-model="price.ends_on" class="mt-1 {{ $inputClass }}">
                        </div>
                        <div class="col-span-6 sm:col-span-2 flex items-end justify-end">
                            <span class="text-sm text-zinc-500" x-show="isLocked(price)">{{ __('Used by orders') }}</span>
                            <button type="button" class="text-sm text-red-600 hover:text-red-900" x-show="! isLocked(price)" @click="prices.splice(index, 1)">{{ __('Remove') }}</button>
                        </div>
                        <template x-for="field in ['type', 'amount', 'amount_min', 'amount_max', 'starts_on', 'ends_on']">
                            <p class="col-span-6 text-sm text-red-600" x-show="error(`prices.${index}.${field}`)" x-text="error(`prices.${index}.${field}`)"></p>
                        </template>
                    </div>
                </template>

                <x-secondary-button type="button" @click="addPrice()">{{ __('Add price') }}</x-secondary-button>
            </div>
        </div>
    </div>
</div>
