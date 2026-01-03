<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Race number') }}</x-slot>
        <x-slot name="description">{{ __('Reserve a race number for a driver.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="bib" value="{{ __('Race Number') }}*" />
                    <x-input id="bib" type="number" name="bib" :value="old('bib', optional($reservation ?? null)->bib)" class="mt-1 block w-full" required autocomplete="bib" />
                    <x-input-error for="bib" class="mt-2" />
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Driver') }}</x-slot>
        <x-slot name="description">{{ __('Driver to which the number is reserved. Driver must register to a race with the same licence to use the reservation.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">

                <div class="col-span-6 sm:col-span-4">
                    @livewire('driver-search')
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence_number" value="{{ __('Licence Number') }}*" />
                    <x-input id="driver_licence_number" type="text" name="driver_licence_number" class="mt-1 block w-full" :value="old('driver_licence_number', optional($reservation ?? null)->driver_licence)"  />
                    <x-input-error for="driver_licence_number" class="mt-2" />
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

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('driver-selected', (event) => {
            const data = event[0];

            // Fill the form fields
            document.getElementById('driver').value = data.driver;
            document.getElementById('driver_licence_number').value = data.licence;
            if (data.email) {
                document.getElementById('contact_email').value = data.email;
            }
        });
    });
</script>

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Reservation expiration') }}</x-slot>
        <x-slot name="description">{{ __('Race numbers can be reserved until a specific date or for the full championship. Leave blank for making the reservation last until the end of the championship.') }} </x-slot>
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