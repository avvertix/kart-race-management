<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Event period') }}</x-slot>
        <x-slot name="description">
            {{ __('When the race takes place.') }}
            {{ __('For single day event specify only the "start date".') }}
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="start" value="{{ __('Start date') }}*" />
                        <x-input id="start" type="date" name="start" class="mt-1 block w-full" required autofocus pattern="\d{4}-\d{2}-\d{2}"  :value="old('start', optional($race ?? null)->event_start_at?->toDateString())" />
                        <x-input-error for="start" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="end" value="{{ __('End Date') }}" />
                        <x-input id="end" type="date" name="end" class="mt-1 block w-full" pattern="\d{4}-\d{2}-\d{2}" :value="old('end', optional($race ?? null)->event_end_at?->toDateString())" />
                        <x-input-error for="end" class="mt-2" />
                    </div>
                </div>
            </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Details') }}</x-slot>
        <x-slot name="description">{{ __('The race details, like title and description.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="title" value="{{ __('Title') }}*" />
                        <x-input id="title" type="text" name="title" class="mt-1 block w-full" required autocomplete="title" :value="old('title', optional($race ?? null)->title)" />
                        <x-input-error for="title" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="description" value="{{ __('Description') }}" />
                        <x-input id="description" type="text" name="description" class="mt-1 block w-full" autocomplete="description" :value="old('description', optional($race ?? null)->description)" />
                        <x-input-error for="description" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="race_type" value="{{ __('Race type') }}" />
                        <x-input-error for="race_type" class="mt-2" />
                        <x-options-selector :options="\App\Models\RaceType::class" id="race_type" name="race_type" class="mt-1" :value="old('race_type', optional($race ?? null)->type?->value ?? null)" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="participants_total_limit" value="{{ __('Maximum number of participants (leave blank for no limit)') }}" />
                        <x-input id="participants_total_limit" type="text" name="participants_total_limit" class="mt-1 block w-full" autocomplete="participants_total_limit"  :value="old('participants_total_limit', optional($race ?? null)->getTotalParticipantLimit())" />
                        <x-input-error for="participants_total_limit" class="mt-2" />
                    </div>
                </div>
            </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Registration') }}</x-slot>
        <x-slot name="description">{{ __('Tune the registration opening period.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="registration_opens_at" value="{{ __('Open the registration at') }}" />
                        <x-input id="registration_opens_at" type="datetime-local" name="registration_opens_at" class="mt-1 block w-full" :value="old('registration_opens_at', optional($race ?? null)->registration_opens_at?->setTimezone(config('races.timezone'))->toDateTimeString())" />
                        <x-input-error for="registration_opens_at" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="registration_closes_at" value="{{ __('Close the registration at') }}" />
                        <x-input id="registration_closes_at" type="datetime-local" name="registration_closes_at" class="mt-1 block w-full" :value="old('registration_closes_at', optional($race ?? null)->registration_closes_at?->setTimezone(config('races.timezone'))->toDateTimeString())" />
                        <x-input-error for="registration_closes_at" class="mt-2" />
                    </div>
                </div>
            </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Track') }}</x-slot>
        <x-slot name="description">{{ __('The race track where the race takes place.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="track" value="{{ __('Track') }}*" />
                        <x-input id="track" type="text" name="track" class="mt-1 block w-full" required autocomplete="track" :value="old('track', optional($race ?? null)->track)" />
                        <x-input-error for="track" class="mt-2" />
                    </div>
                </div>
            </div>
    </div>
</div>


<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Hide race') }}</x-slot>
        <x-slot name="description">{{ __('Allow to hide the race from public listing even if registrations are open.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <label for="hidden" class="flex items-center">
                        <x-checkbox id="hidden" name="hidden" value="true" :checked="(optional($race ?? null)->hide)"/>
                        <span class="ml-2">{{ __('Hide the race from public listing') }}</span>
                        <x-input-error for="hidden" class="mt-2" />
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>