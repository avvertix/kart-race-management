<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Reservation') }}</x-slot>
        <x-slot name="description">{{ __('Reserve race numbers before other participants. Participants must register to a race with the same driver name and licence to use the reserved number.') }} </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="bib" value="{{ __('Race Number') }}*" />
                    <x-input id="bib" type="number" name="bib" :value="old('bib', optional($reservation ?? null)->bib)" class="mt-1 block w-full" required autocomplete="bib" />
                    <x-input-error for="bib" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver" value="{{ __('Driver name and surname') }}*" />
                    <x-input id="driver" type="text" name="driver" :value="old('name', optional($reservation ?? null)->driver)" class="mt-1 block w-full" required autocomplete="name" />
                    <x-input-error for="driver" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="contact_email" value="{{ __('E-Mail') }}" />
                    <x-input id="contact_email" type="email" name="contact_email" class="mt-1 block w-full" :value="old('contact_email', optional($reservation ?? null)->contact_email)"  />
                    <x-input-error for="contact_email" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence_number" value="{{ __('Licence Number') }}*" />
                    <x-input id="driver_licence_number" type="text" name="driver_licence_number" class="mt-1 block w-full" :value="old('driver_licence_number', optional($reservation ?? null)->driver_licence)"  />
                    <x-input-error for="driver_licence_number" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence_type" value="{{ __('Licence Type') }}" />
                    <x-input-error for="driver_licence_type" class="mt-2" />
                    <x-options-selector id="driver_licence_type" name="driver_licence_type" class="mt-1" :value="old('driver_licence_type', optional($reservation ?? null)->licence_type?->value)"  />
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Reservation expiration') }}</x-slot>
        <x-slot name="description">{{ __('Race numbers can be reserved for a specific time or for the full championship. Leave blank for keeping the reservation for the whole championship or select a date.') }} </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="reservation_expiration_date" value="{{ __('Date of expiration of the reservation') }}" />
                    <x-input id="reservation_expiration_date" type="date" name="reservation_expiration_date" class="mt-1 block w-full" :value="old('reservation_expiration_date', optional($reservation ?? null)->reservation_expires_at)"   pattern="\d{4}-\d{2}-\d{2}"/>
                    <x-input-error for="reservation_expiration_date" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>