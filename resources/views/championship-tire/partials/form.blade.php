<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Details') }}</x-slot>
        <x-slot name="description">{{ __('Tire model name and price.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="name" value="{{ __('Name') }}*" />
                    <x-input id="name" type="text" name="name" :value="old('name', optional($tire ?? null)->name)" class="mt-1 block w-full" required autocomplete="name" />
                    <x-input-error for="name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="price" value="{{ __('Price') }}*" />
                    <p class="text-zinc-600 text-sm">{{ __('Specify the price in cents. For example if the price is 140,15 € specify 14015.') }}</p>
                    <x-input id="price" type="text" name="price" :value="old('price', optional($tire ?? null)->price)" class="mt-1 block w-full" required autocomplete="price" />
                    <x-input-error for="price" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>