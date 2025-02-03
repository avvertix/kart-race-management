<x-validation-errors class="mb-4" />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Race number and category') }}</x-slot>
        <x-slot name="description">
            {{ __('Insert your race number and category.') }}<br/>
            {{ __('The race number will be uniquely assigned to you for the whole championship.') }}<br/>
            {{ __('If you previously participate in a race within the championship use the same race number.') }}<br/>
            <em>{{ __('All information are required') }}</em>.
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <livewire:race-number :championship="$race->championship" :value="old('bib', optional($participant ?? null)->bib)" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="category" value="{{ __('Category') }}*" />
                    <x-input-error for="category" class="mt-2" />
                    
                    <livewire:category-selector name="category" class="" :championship="$race->championship" :value="old('category', optional($participant ?? null)->category)" />
                </div>
            </div>
        </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Driver') }}</x-slot>
        <x-slot name="description">
            {{ __('The driver that is participating to the competition.') }}<br/>
            {{ __('The licence and race number will be used to identify the participant in the whole championship.') }}<br/>
            <em>{{ __('required') }}</em>.
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">

                @php
                    $driver = optional($participant ?? null)->driver;
                @endphp
                
                
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_first_name" value="{{ __('Name') }}*" />
                    <x-input id="driver_first_name" type="text" name="driver_first_name" class="mt-1 block w-full" :value="old('driver_first_name', optional($participant ?? null)->first_name)"  />
                    <x-input-error for="driver_first_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_last_name" value="{{ __('Surname') }}*" />
                    <x-input id="driver_last_name" type="text" name="driver_last_name" class="mt-1 block w-full" :value="old('driver_last_name', optional($participant ?? null)->last_name)"  />
                    <x-input-error for="driver_last_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_fiscal_code" value="{{ __('Fiscal code') }}*" />
                    <x-input id="driver_fiscal_code" type="text" name="driver_fiscal_code" class="mt-1 block w-full" :value="old('driver_fiscal_code', $driver['fiscal_code'] ?? null)"  />
                    <x-input-error for="driver_fiscal_code" class="mt-2" />
                </div>
                @useCompleteRegistrationForm()
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence_type" value="{{ __('Licence Type') }}*" />
                    <x-input-error for="driver_licence_type" class="mt-2" />
                    <x-options-selector id="driver_licence_type" name="driver_licence_type" class="mt-1" :value="old('driver_licence_type', $driver['licence_type'] ?? null)"  />
                </div>
                @enduseCompleteRegistrationForm
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence_number" value="{{ __('Licence Number') }}*" />
                    <p class="text-sm text-zinc-700">{{ __('Use a licence number provided by :provider.', ['provider' => config('races.licence.provider')]) }} <a class="underline" href="mailto:{{config('races.organizer.email')}}?subject=Non%20ho%20una%20licenza%20valida%20per%20l'iscrizione">{{ __('Don\'t have a licence, contact the organizer!') }}</a></p>
                    <x-input id="driver_licence_number" type="text" name="driver_licence_number" class="mt-1 block w-full" :value="old('driver_licence_number', $driver['licence_number'] ?? null)"  />
                    <x-input-error for="driver_licence_number" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_nationality" value="{{ __('Nationality') }}*" />
                    <x-input id="driver_nationality" type="text" name="driver_nationality" class="mt-1 block w-full" :value="old('driver_nationality', $driver['nationality'] ?? null)"  />
                    <x-input-error for="driver_nationality" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_email" value="{{ __('E-Mail') }}*" />
                    <p class="text-sm">{{ __('Please enter an existing email address as you will receive a messgae to confirm for the participation.') }}</p>
                    <x-input id="driver_email" type="email" name="driver_email" class="mt-1 block w-full" :value="old('driver_email', $driver['email'] ?? null)"  />
                    <x-input-error for="driver_email" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_phone" value="{{ __('Phone number') }}*" />
                    <x-input id="driver_phone" type="text" name="driver_phone" class="mt-1 block w-full" :value="old('driver_phone', $driver['phone'] ?? null)"  />
                    <x-input-error for="driver_phone" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_birth_date" value="{{ __('Birth date') }}*" />
                    <p class="text-sm">{{ __('Supported format:') }} <code>{{ trans('date-input.placeholder') }}</code></p>
                    <x-input-date id="driver_birth_date" name="driver_birth_date" class="mt-1 block w-full" :value="old('driver_birth_date', $driver['birth_date'] ?? null)" />
                    <x-input-error for="driver_birth_date" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_birth_place" value="{{ __('Birth place') }}*" />
                    <x-input id="driver_birth_place" type="text" name="driver_birth_place" class="mt-1 block w-full" :value="old('driver_birth_place', $driver['birth_place'] ?? null)"  />
                    <x-input-error for="driver_birth_place" class="mt-2" />
                </div>

                @useCompleteRegistrationForm()
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_medical_certificate_expiration_date" value="{{ __('Date of expiration of the medical certificate') }}*" />
                    <p class="text-sm">{{ __('Supported format:') }} <code>{{ trans('date-input.placeholder') }}</code></p>
                    <x-input-date id="driver_medical_certificate_expiration_date" name="driver_medical_certificate_expiration_date" class="mt-1 block w-full" :value="old('driver_medical_certificate_expiration_date', $driver['medical_certificate_expiration_date'] ?? null)"/>
                    <x-input-error for="driver_medical_certificate_expiration_date" class="mt-2" />
                </div>
                @enduseCompleteRegistrationForm

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_residence" value="{{ __('Residence address') }}*" />
                    <x-input-error for="driver_residence_address" class="mt-2" />
                    <x-address id="driver_residence" type="text" name="driver_residence" class="mt-1 block w-full" :value="$driver['residence_address'] ?? null"  />
                </div>

                @useCompleteRegistrationForm()
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_sex" value="{{ __('Sex') }}*" />
                    <x-input-error for="driver_sex" class="mt-2" />
                    <x-options-selector :options="\App\Models\Sex::class" id="driver_sex" name="driver_sex" class="mt-1 " :value="old('driver_sex', $driver['sex'] ?? null)"  />
                </div>
                @enduseCompleteRegistrationForm
                
            </div>
        </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Competitor') }}</x-slot>
        <x-slot name="description">
            {{ __('The competitor that brings the driver to the race.') }}<br/>
            <em>{{ __('mandatory if driver is underage') }}</em>.
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

        @php
            $competitor = optional($participant ?? null)->competitor;
        @endphp
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_first_name" value="{{ __('Name') }}*" />
                    <x-input id="competitor_first_name" type="text" name="competitor_first_name" class="mt-1 block w-full" :value="old('competitor_first_name', $competitor['first_name'] ?? null)"  />
                    <x-input-error for="competitor_first_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_last_name" value="{{ __('Surname') }}*" />
                    <x-input id="competitor_last_name" type="text" name="competitor_last_name" class="mt-1 block w-full" :value="old('competitor_last_name', $competitor['last_name'] ?? null)"  />
                    <x-input-error for="competitor_last_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_fiscal_code" value="{{ __('Fiscal Code') }}*" />
                    <x-input id="competitor_fiscal_code" type="text" name="competitor_fiscal_code" class="mt-1 block w-full" :value="old('competitor_fiscal_code', $competitor['fiscal_code'] ?? null)"  />
                    <x-input-error for="competitor_fiscal_code" class="mt-2" />
                </div>
                @useCompleteRegistrationForm()
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_licence_type" value="{{ __('Licence Type') }}*" />
                    <x-input-error for="competitor_licence_type" class="mt-2" />
                    <x-options-selector :options="\App\Models\CompetitorLicence::class" id="competitor_licence_type" name="competitor_licence_type" class="mt-1  " :value="old('competitor_licence_type', $competitor['licence_type'] ?? null)"  />
                </div>
                @enduseCompleteRegistrationForm
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_licence_number" value="{{ __('Licence Number') }}*" />
                    <x-input id="competitor_licence_number" type="text" name="competitor_licence_number" class="mt-1 block w-full" :value="old('competitor_licence_number', $competitor['licence_number'] ?? null)"  />
                    <x-input-error for="competitor_licence_number" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_nationality" value="{{ __('Nationality') }}*" />
                    <x-input id="competitor_nationality" type="text" name="competitor_nationality" class="mt-1 block w-full" :value="old('competitor_nationality', $competitor['nationality'] ?? null)"  />
                    <x-input-error for="competitor_nationality" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_email" value="{{ __('E-Mail') }}*" />
                    <x-input id="competitor_email" type="email" name="competitor_email" class="mt-1 block w-full" :value="old('competitor_email', $competitor['email'] ?? null)"  />
                    <x-input-error for="competitor_email" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_phone" value="{{ __('Phone number') }}*" />
                    <x-input id="competitor_phone" type="text" name="competitor_phone" class="mt-1 block w-full" :value="old('competitor_phone', $competitor['phone'] ?? null)"  />
                    <x-input-error for="competitor_phone" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_birth_date" value="{{ __('Birth date') }}*" />
                    <p class="text-sm">{{ __('Supported format:') }} <code>{{ trans('date-input.placeholder') }}</code></p>
                    <x-input-date id="competitor_birth_date" name="competitor_birth_date" class="mt-1 block w-full" :value="old('competitor_birth_date', $competitor['birth_date'] ?? null)" />
                    <x-input-error for="competitor_birth_date" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_birth_place" value="{{ __('Birth place') }}*" />
                    <x-input id="competitor_birth_place" type="text" name="competitor_birth_place" class="mt-1 block w-full" :value="old('competitor_birth_place', $competitor['birth_place'] ?? null)"  />
                    <x-input-error for="competitor_birth_place" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_residence" value="{{ __('Residence address') }}*" />
                    <x-input-error for="competitor_residence_address" class="mt-2" />
                    <x-address id="competitor_residence" type="text" name="competitor_residence" :value="$competitor['residence_address'] ?? null" class="mt-1 block w-full"  />
                </div>
                
            </div>
        </div>
    </div>
</div>

@useCompleteRegistrationForm()
<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Mechanic') }}</x-slot>
        <x-slot name="description">{{ __('The mechanic that will assist the driver during the race.') }}<br/><em>{{ __('optional') }}</em>.</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5">
            @php
                $mechanic = optional($participant ?? null)->mechanic;
            @endphp
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="mechanic_licence_number" value="{{ __('Licence number') }}" />
                    <x-input id="mechanic_licence_number" type="text" name="mechanic_licence_number" class="mt-1 block w-full" :value="old('mechanic_licence_number', $mechanic['licence_number'] ?? null)" autocomplete="mechanic_licence_number" />
                    <x-input-error for="mechanic_licence_number" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="mechanic_name" value="{{ __('Name and Surname') }}" />
                    <x-input id="mechanic_name" type="text" name="mechanic_name" class="mt-1 block w-full" :value="old('mechanic_name', $mechanic['name'] ?? null)" autocomplete="mechanic_name" />
                    <x-input-error for="mechanic_name" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Vehicle') }}</x-slot>
        <x-slot name="description">{{ __('The characteristics of the vehicle.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="vehicle_chassis_manufacturer" value="{{ __('Chassis Manufacturer') }}*" />
                    <x-input id="vehicle_chassis_manufacturer" type="text" name="vehicle_chassis_manufacturer" class="mt-1 block w-full" :value="old('vehicle_chassis_manufacturer', optional($participant ?? null)->vehicles[0]['chassis_manufacturer'] ?? null)"  autocomplete="chassis_manufacturer" />
                    <x-input-error for="vehicle_chassis_manufacturer" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="vehicle_engine_manufacturer" value="{{ __('Engine Manufacturer') }}*" />
                    <x-input-error for="vehicle_engine_manufacturer" class="mt-2" />
                    <livewire:engine-input  :value="old('vehicle_engine_manufacturer', optional($participant ?? null)->vehicles[0]['engine_manufacturer'] ?? null)"  autocomplete="engine_manufacturer" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="vehicle_engine_model" value="{{ __('Engine Model') }}*" />
                    <x-input id="vehicle_engine_model" type="text" name="vehicle_engine_model" class="mt-1 block w-full" :value="old('vehicle_engine_model', optional($participant ?? null)->vehicles[0]['engine_model'] ?? null)"  autocomplete="engine_model" />
                    <x-input-error for="vehicle_engine_model" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="vehicle_oil_manufacturer" value="{{ __('Oil Manufacturer') }}*" />
                    <x-input id="vehicle_oil_manufacturer" type="text" name="vehicle_oil_manufacturer" class="mt-1 block w-full" :value="old('vehicle_oil_manufacturer', optional($participant ?? null)->vehicles[0]['oil_manufacturer'] ?? null)"  autocomplete="oil_manufacturer" />
                    <x-input-error for="vehicle_oil_manufacturer" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="vehicle_oil_type" value="{{ __('Oil Type') }}" />
                    <x-input id="vehicle_oil_type" type="text" name="vehicle_oil_type" class="mt-1 block w-full" :value="old('vehicle_oil_type', optional($participant ?? null)->vehicles[0]['oil_type'] ?? null)" autocomplete="oil_type" />
                    <x-input-error for="vehicle_oil_type" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="vehicle_oil_percentage" value="{{ __('Oil Percentage') }}*" />
                    <x-input id="vehicle_oil_percentage" type="text" name="vehicle_oil_percentage" class="mt-1 block w-full" :value="old('vehicle_oil_percentage', optional($participant ?? null)->vehicles[0]['oil_percentage'] ?? null)"  autocomplete="oil_percentage" />
                    <x-input-error for="vehicle_oil_percentage" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>
@enduseCompleteRegistrationForm