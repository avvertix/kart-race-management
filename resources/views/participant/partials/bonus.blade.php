
<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Bonus') }}</x-slot>
        <x-slot name="description">{{ __('The participant can use a bonus given by the organizer.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="bonus" class="flex items-center">
                            <x-checkbox id="bonus" name="bonus" value="true" :checked="(optional($participant ?? null)->use_bonus)" />
                            <span class="ml-2">{{ __('Use a bonus') }}</span>
                        </label>
                    </div>
                </div>
            </div>
    </div>
</div>