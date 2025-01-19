<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Details') }}</x-slot>
        <x-slot name="description">{{ __('Category name, description and allowed tires.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="name" value="{{ __('Name') }}*" />
                    <x-input id="name" type="text" name="name" :value="old('name', optional($category ?? null)->name)" class="mt-1 block w-full" required autocomplete="name" />
                    <x-input-error for="name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="description" value="{{ __('Description') }}" />
                    <x-input id="description" type="text" name="description" :value="old('description', optional($category ?? null)->description)" class="mt-1 block w-full" autocomplete="description" />
                    <x-input-error for="description" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="short_name" value="{{ __('Short Name') }}" />
                    <p class="text-zinc-600 text-sm">{{ __('The alternate name for this category, usually for timekeeping services.') }}</p>
                    <x-input id="short_name" type="text" name="short_name" :value="old('short_name', optional($category ?? null)->short_name)" class="mt-1 block w-full" autocomplete="short_name" />
                    <x-input-error for="short_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="tire" value="{{ __('Allowed tires') }}" />
                    <p class="text-zinc-600 text-sm">{{ __('The tires that are allowed in this category. Leave empty for allowing all tires.') }}</p>
                    <select name="tire" id="tire">
                            <option value="" @selected(is_null(old('tire', optional($category ?? null)->tire))) >{{ __('No tire required') }}</option>
                        @foreach ($tires as $tire)
                            <option value="{{ $tire->getKey() }}" @selected(old('tire', optional($category ?? null)->tire?->getKey()) === $tire->getKey()) >{{ $tire->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="tire" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Enabled') }}</x-slot>
        <x-slot name="description">{{ __('If participants are allowed to select this category during registration.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <label for="enabled" class="flex items-center">
                        <x-checkbox id="enabled" name="enabled" value="1" :checked="old('enabled', optional($category ?? null)->enabled ?? true)" />
                        <span class="ml-2">{{ __('Allow participants to select this category') }}</span>
                        <x-input-error for="enabled" class="mt-2" />
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>