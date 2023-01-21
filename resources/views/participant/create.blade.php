<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('races.show', $race) }}">{{ $race->title }}</a></span>
            <span>/</span>
            <span>{{ __('Add new participant') }}</span>
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-jet-validation-errors class="mb-4" />

<form method="POST" action="{{ route('races.participants.store', $race) }}">
@csrf
        
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Race number and category') }}</x-slot>
                    <x-slot name="description">
                        {{ __('Insert your race number and category.') }}<br/>
                        {{ __('If you previously participate in a race within the championship use the same number.') }}<br/>
                        <em>{{ __('') }}</em>.
                    </x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="bib" value="{{ __('Number') }}*" />
                                    <x-jet-input id="bib" type="number" name="bib" class="mt-1 block w-full" :value="old('bib')"  autofocus />
                                    <x-jet-input-error for="bib" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="category" value="{{ __('Category') }}*" />
                                    <x-jet-input-error for="category" class="mt-2" />
                                    
                                    <livewire:category-selector name="category" class="mt-1 block w-full" :value="old('category')" />
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Driver') }}</x-slot>
                    <x-slot name="description">
                        {{ __('The driver that is participating to the competition.') }}<br/>
                        {{ __('Search for a previously registered driver using name, race number or licence. If no driver is already participating in the championship you can create a new driver.') }}<br/>
                        {{ __('After selecting an existing driver you can change category, licence and name for this race') }}<br/>
                        {{ __('The "race number" is uniquely assigned to a driver within the championship and cannot be changed.') }}<br/>
                        <em>{{ __('') }}</em>.
                    </x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                
                                
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_first_name" value="{{ __('Name') }}*" />
                                    <x-jet-input id="driver_first_name" type="text" name="driver_first_name" class="mt-1 block w-full" :value="old('driver_first_name')"  />
                                    <x-jet-input-error for="driver_first_name" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_last_name" value="{{ __('Surname') }}*" />
                                    <x-jet-input id="driver_last_name" type="text" name="driver_last_name" class="mt-1 block w-full" :value="old('driver_last_name')"  />
                                    <x-jet-input-error for="driver_last_name" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_licence_type" value="{{ __('Licence Type') }}*" />
                                    <x-jet-input-error for="driver_licence_type" class="mt-2" />
                                    <x-options-selector id="driver_licence_type" name="driver_licence_type" class="mt-1" :value="old('driver_licence_type')"  />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_licence_number" value="{{ __('Licence Number') }}*" />
                                    <x-jet-input id="driver_licence_number" type="text" name="driver_licence_number" class="mt-1 block w-full" :value="old('driver_licence_number')"  />
                                    <x-jet-input-error for="driver_licence_number" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_nationality" value="{{ __('Nationality') }}*" />
                                    <x-jet-input id="driver_nationality" type="text" name="driver_nationality" class="mt-1 block w-full" :value="old('driver_nationality')"  />
                                    <x-jet-input-error for="driver_nationality" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_email" value="{{ __('E-Mail') }}*" />
                                    <x-jet-input id="driver_email" type="email" name="driver_email" class="mt-1 block w-full" :value="old('driver_email')"  />
                                    <x-jet-input-error for="driver_email" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_phone" value="{{ __('Phone number') }}*" />
                                    <x-jet-input id="driver_phone" type="text" name="driver_phone" class="mt-1 block w-full" :value="old('driver_phone')"  />
                                    <x-jet-input-error for="driver_phone" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_birth_date" value="{{ __('Birth date') }}*" />
                                    <x-jet-input id="driver_birth_date" type="date" name="driver_birth_date" class="mt-1 block w-full" :value="old('driver_birth_date')" pattern="\d{4}-\d{2}-\d{2}" />
                                    <x-jet-input-error for="driver_birth_date" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_birth_place" value="{{ __('Birth place') }}*" />
                                    <x-jet-input id="driver_birth_place" type="text" name="driver_birth_place" class="mt-1 block w-full" :value="old('driver_birth_place')"  />
                                    <x-jet-input-error for="driver_birth_place" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_medical_certificate_expiration_date" value="{{ __('Date of expiration of the medical certificate') }}*" />
                                    <x-jet-input id="driver_medical_certificate_expiration_date" type="date" name="driver_medical_certificate_expiration_date" class="mt-1 block w-full" :value="old('driver_medical_certificate_expiration_date')"   pattern="\d{4}-\d{2}-\d{2}"/>
                                    <x-jet-input-error for="driver_medical_certificate_expiration_date" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_residence" value="{{ __('Residence address') }}*" />
                                    <x-jet-input-error for="driver_residence_address" class="mt-2" />
                                    <x-address id="driver_residence" type="text" name="driver_residence" class="mt-1 block w-full"  />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_sex" value="{{ __('driver_sex') }}*" />
                                    <x-jet-input-error for="driver_sex" class="mt-2" />
                                    <x-options-selector :options="\App\Models\Sex::class" id="driver_sex" name="driver_sex" class="mt-1 " :value="old('driver_sex')"  />
                                </div>
                                
                            </div>
                        </div>
                </div>
            </div>

            <x-jet-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Competitor') }}</x-slot>
                    <x-slot name="description">
                        {{ __('The competitor that brings the driver to the race.') }}<br/>
                        {{ __('Search for a competitor using the licence number or insert a new one.') }}<br/>
                        <em>{{ __(' if driver is underage') }}</em>.</x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_first_name" value="{{ __('Name') }}*" />
                                    <x-jet-input id="competitor_first_name" type="text" name="competitor_first_name" class="mt-1 block w-full" :value="old('competitor_first_name')"  />
                                    <x-jet-input-error for="competitor_first_name" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_last_name" value="{{ __('Surname') }}*" />
                                    <x-jet-input id="competitor_last_name" type="text" name="competitor_last_name" class="mt-1 block w-full" :value="old('competitor_last_name')"  />
                                    <x-jet-input-error for="competitor_last_name" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_licence_type" value="{{ __('Licence Type') }}*" />
                                    <x-jet-input-error for="competitor_licence_type" class="mt-2" />
                                    <x-options-selector :options="\App\Models\CompetitorLicence::class" id="competitor_licence_type" name="competitor_licence_type" class="mt-1  " :value="old('competitor_licence_type')"  />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_licence_number" value="{{ __('Licence Number') }}*" />
                                    <x-jet-input id="competitor_licence_number" type="text" name="competitor_licence_number" class="mt-1 block w-full" :value="old('competitor_licence_number')"  />
                                    <x-jet-input-error for="competitor_licence_number" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_nationality" value="{{ __('Nationality') }}*" />
                                    <x-jet-input id="competitor_nationality" type="text" name="competitor_nationality" class="mt-1 block w-full" :value="old('competitor_nationality')"  />
                                    <x-jet-input-error for="competitor_nationality" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_email" value="{{ __('E-Mail') }}*" />
                                    <x-jet-input id="competitor_email" type="email" name="competitor_email" class="mt-1 block w-full" :value="old('competitor_email')"  />
                                    <x-jet-input-error for="competitor_email" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_phone" value="{{ __('Phone number') }}*" />
                                    <x-jet-input id="competitor_phone" type="text" name="competitor_phone" class="mt-1 block w-full" :value="old('competitor_phone')"  />
                                    <x-jet-input-error for="competitor_phone" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_birth_date" value="{{ __('Birth date') }}*" />
                                    <x-jet-input id="competitor_birth_date" type="date" name="competitor_birth_date" class="mt-1 block w-full" :value="old('competitor_birth_date')" pattern="\d{4}-\d{2}-\d{2}" />
                                    <x-jet-input-error for="competitor_birth_date" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_birth_place" value="{{ __('Birth place') }}*" />
                                    <x-jet-input id="competitor_birth_place" type="text" name="competitor_birth_place" class="mt-1 block w-full" :value="old('competitor_birth_place')"  />
                                    <x-jet-input-error for="competitor_birth_place" class="mt-2" />
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_residence" value="{{ __('Residence address') }}*" />
                                    <x-jet-input-error for="competitor_residence_address" class="mt-2" />
                                    <x-address id="competitor_residence" type="text" name="competitor_residence" class="mt-1 block w-full"  />
                                </div>
                                
                            </div>
                        </div>
                </div>
            </div>

            <x-jet-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Mechanic') }}</x-slot>
                    <x-slot name="description">{{ __('The mechanic that will assist the driver during the race.') }}<br/><em>{{ __('optional') }}</em>.</x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="mechanic_licence_number" value="{{ __('Licence number') }}" />
                                    <x-jet-input id="mechanic_licence_number" type="text" name="mechanic_licence_number" class="mt-1 block w-full" :value="old('mechanic_licence_number')" autocomplete="mechanic_licence_number" />
                                    <x-jet-input-error for="mechanic_licence_number" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="mechanic_name" value="{{ __('Name and Surname') }}" />
                                    <x-jet-input id="mechanic_name" type="text" name="mechanic_name" class="mt-1 block w-full" :value="old('mechanic_name')" autocomplete="mechanic_name" />
                                    <x-jet-input-error for="mechanic_name" class="mt-2" />
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <x-jet-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Vehicle') }}</x-slot>
                    <x-slot name="description">{{ __('The characteristics of the vehicle.') }}</x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="vehicle_chassis_manufacturer" value="{{ __('Chassis Manufacturer') }}*" />
                                    <x-jet-input id="vehicle_chassis_manufacturer" type="text" name="vehicle_chassis_manufacturer" class="mt-1 block w-full" :value="old('vehicle_chassis_manufacturer')"  autocomplete="chassis_manufacturer" />
                                    <x-jet-input-error for="vehicle_chassis_manufacturer" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="vehicle_engine_manufacturer" value="{{ __('Engine Manufacturer') }}*" />
                                    <x-jet-input id="vehicle_engine_manufacturer" type="text" name="vehicle_engine_manufacturer" class="mt-1 block w-full" :value="old('vehicle_engine_manufacturer')"  autocomplete="engine_manufacturer" />
                                    <x-jet-input-error for="vehicle_engine_manufacturer" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="vehicle_engine_model" value="{{ __('Engine Model') }}*" />
                                    <x-jet-input id="vehicle_engine_model" type="text" name="vehicle_engine_model" class="mt-1 block w-full" :value="old('vehicle_engine_model')"  autocomplete="engine_model" />
                                    <x-jet-input-error for="vehicle_engine_model" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="vehicle_oil_manufacturer" value="{{ __('Oil Manufacturer') }}*" />
                                    <x-jet-input id="vehicle_oil_manufacturer" type="text" name="vehicle_oil_manufacturer" class="mt-1 block w-full" :value="old('vehicle_oil_manufacturer')"  autocomplete="oil_manufacturer" />
                                    <x-jet-input-error for="vehicle_oil_manufacturer" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="vehicle_oil_type" value="{{ __('Oil Type') }}" />
                                    <x-jet-input id="vehicle_oil_type" type="text" name="vehicle_oil_type" class="mt-1 block w-full" :value="old('vehicle_oil_type')" autocomplete="oil_type" />
                                    <x-jet-input-error for="vehicle_oil_type" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="vehicle_oil_percentage" value="{{ __('Oil Percentage') }}*" />
                                    <x-jet-input id="vehicle_oil_percentage" type="text" name="vehicle_oil_percentage" class="mt-1 block w-full" :value="old('vehicle_oil_percentage')"  autocomplete="oil_percentage" />
                                    <x-jet-input-error for="vehicle_oil_percentage" class="mt-2" />
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <x-jet-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Bonus') }}</x-slot>
                    <x-slot name="description">{{ __('The participant can use a bonus given by the organizer.') }}</x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">

                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <label for="bonus" class="flex items-center">
                                        <x-jet-checkbox id="bonus" name="bonus" />
                                        <span class="ml-2">{{ __('Apply a bonus') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <x-jet-section-border />
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Consents') }}</x-slot>
                    <x-slot name="description">{{ __('Privacy is important to us.') }}</x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">

                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <p>{{ __('As a race manager you cannot express privacy consents for the driver, the competitor and the mechanic.') }}</p>
                                    <p class="mt-1">{{ __('Please remind them to look for the privacy policy on the organizer\'s website.') }}</p>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                
                <div></div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <x-jet-button class="">
                                {{ __('Add participant') }}
                            </x-jet-button>
                        </div>
                </div>
            </div>

</form>
        </div>
    </div>
</x-app-layout>
