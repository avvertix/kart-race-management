<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ $option->exists ? $option->title : __('Add an enrollment option') }} - {{ $championship->title }}
    </x-slot>

    <x-validation-errors class="mb-4" />

    @php
        $selectedCategories = array_map('intval', old('licence_categories', $option->licence_categories ?? []));
        $selectedMode = old('entitlement_mode', $option->entitlement_mode?->value ?? '');
        $entitlementAmount = old('entitlement_amount', $option->entitlement_mode === \App\Enums\EntitlementMode::Balance ? number_format(((int) $option->entitlement_amount) / 100, 2, '.', '') : $option->entitlement_amount);
    @endphp

    <form method="POST" action="{{ $option->exists ? route('enrollment-options.update', $option) : route('championships.enrollment-options.store', $championship) }}" class="max-w-2xl space-y-6">
        @csrf
        @if ($option->exists)
            @method('PUT')
        @endif

        <div>
            <x-label for="title" value="{{ __('Title') }}" />
            <x-input id="title" type="text" name="title" class="mt-1 block w-full" :value="old('title', $option->title)" required maxlength="150" />
            <x-input-error for="title" class="mt-2" />
        </div>

        <div>
            <x-label for="description" value="{{ __('Description') }}" />
            <x-textarea id="description" name="description" rows="3" class="mt-1 block w-full">{{ old('description', $option->description) }}</x-textarea>
            <x-input-error for="description" class="mt-2" />
        </div>

        <div>
            <x-label for="amount" value="{{ __('Amount (€)') }}" />
            <x-input id="amount" type="text" inputmode="decimal" name="amount" class="mt-1 block w-40" :value="old('amount', number_format($option->amount / 100, 2, '.', ''))" required />
            <p class="mt-1 text-sm text-zinc-500">{{ __('0 for a free enrollment. The same amount whatever the licence.') }}</p>
            <x-input-error for="amount" class="mt-2" />
        </div>

        <fieldset class="space-y-2">
            <legend class="text-sm font-medium text-zinc-900">{{ __('Includes') }}</legend>
            <label class="flex items-start gap-3">
                <x-checkbox name="covers_licence_renewal" value="1" :checked="$errors->any() ? (bool) old('covers_licence_renewal') : $option->covers_licence_renewal" />
                <span class="text-sm text-zinc-900">{{ __('Covers the licence renewal') }}</span>
            </label>
            <label class="flex items-start gap-3">
                <x-checkbox name="reserves_race_number" value="1" :checked="$errors->any() ? (bool) old('reserves_race_number') : $option->reserves_race_number" />
                <span class="text-sm text-zinc-900">{{ __('Reserves a race number') }}</span>
            </label>
        </fieldset>

        @if ($championship->entitlements_enabled)
            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-zinc-900">{{ __('Race fee discount') }}</legend>
                <label class="flex items-start gap-3">
                    <input type="radio" name="entitlement_mode" value="" @checked($selectedMode === '') class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                    <span class="text-sm text-zinc-900">{{ __('None') }}</span>
                </label>
                @foreach (\App\Enums\EntitlementMode::cases() as $mode)
                    <label class="flex items-start gap-3">
                        <input type="radio" name="entitlement_mode" value="{{ $mode->value }}" @checked($selectedMode === $mode->value) class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300">
                        <span class="text-sm text-zinc-900">{{ $mode->label() }}</span>
                    </label>
                @endforeach
                <div>
                    <x-label for="entitlement_amount" value="{{ __('Amount') }}" />
                    <x-input id="entitlement_amount" type="text" inputmode="decimal" name="entitlement_amount" class="mt-1 block w-40" :value="$entitlementAmount" />
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Number of race fees, or euro of balance.') }}</p>
                </div>
                <x-input-error for="entitlement_mode" class="mt-2" />
                <x-input-error for="entitlement_amount" class="mt-2" />
            </fieldset>
        @endif

        <fieldset class="space-y-2">
            <legend class="text-sm font-medium text-zinc-900">{{ __('Licence categories') }}</legend>
            <p class="text-sm text-zinc-500">{{ __('Offer the option only to licences of these categories. None selected means any licence.') }}</p>
            @foreach (\App\Models\DriverLicence::cases() as $category)
                <label class="flex items-start gap-3">
                    <x-checkbox name="licence_categories[]" value="{{ $category->value }}" :checked="in_array($category->value, $selectedCategories, true)" />
                    <span class="text-sm text-zinc-900">{{ $category->localizedName() }}</span>
                </label>
            @endforeach
            <x-input-error for="licence_categories" class="mt-2" />
            <x-input-error for="licence_categories.*" class="mt-2" />
        </fieldset>

        @if ($option->exists)
            <label class="flex items-start gap-3">
                <x-checkbox name="active" value="1" :checked="$errors->any() ? (bool) old('active') : $option->active" />
                <span class="text-sm text-zinc-900">{{ __('Offered to drivers') }}</span>
            </label>
        @endif

        <div>
            <x-label for="sort" value="{{ __('Display order') }}" />
            <x-input id="sort" type="number" min="0" name="sort" class="mt-1 block w-24" :value="old('sort', $option->sort ?? 0)" />
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit">{{ __('Save') }}</x-button>
            <a href="{{ route('championships.enrollment-settings.edit', $championship) }}" class="text-sm text-zinc-600 hover:text-zinc-900">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-championship-page-layout>
