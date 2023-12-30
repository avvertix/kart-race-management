<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Details') }}</x-slot>
        <x-slot name="description">{{ __('Category name and description.') }}</x-slot>
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
            </div>
        </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Enabled') }}</x-slot>
        <x-slot name="description">{{ __('Allow participants to select this category.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <label for="enabled" class="flex items-center">
                        <x-checkbox id="enabled" name="enabled" value="1" :checked="old('enabled', optional($category ?? null)->enabled ?? true)" />
                        <span class="ml-2">{{ __('Enable the selection of this category') }}</span>
                        <x-input-error for="enabled" class="mt-2" />
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>