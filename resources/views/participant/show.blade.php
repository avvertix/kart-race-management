<x-app-layout>
    <x-slot name="header">
        <div class="relative border-b-2 border-zinc-200 pb-5 sm:pb-0">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                    {{ $participant->bib }}
                    {{ $participant->first_name }}
                    {{ $participant->last_name }}
                    <p class="text-base font-light"><a href="{{ route('races.participants.index', $participant->race) }}">{{ $participant->race->title }}</a></p>
                    <p class="text-base font-light">{{ $participant->championship->title }}</p>
                </h2>
                <div class="mt-3 flex md:absolute md:top-3 md:right-0 md:mt-0 gap-2">

                    @can('update', $participant)
                        <x-button-link href="{{ route('participants.edit', $participant) }}">
                            {{ __('Edit participant') }}
                        </x-button-link>
                    @endcan

                </div>
            </div>
            <div class="mt-2 flex items-center text-sm text-zinc-500">
                <div>
                    <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                    </svg>
                </div>
                {{ $participant->created_at }}
            </div>
            
        </div>

        @if (session('message'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('message') }}
        </div>
    @endif
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                    <x-jet-label value="{{ __('Number') }}*" />
                                    {{ $participant->bib }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label value="{{ __('Category') }}*" />
                                    {{ $participant->category }}
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
                                    {{ $participant->first_name }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_last_name" value="{{ __('Surname') }}*" />
                                    {{ $participant->last_name }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_licence_type" value="{{ __('Licence Type') }}*" />
                                    {{ $participant->licence_type?->value }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_licence_number" value="{{ __('Licence Number') }}*" />
                                    {{ $participant->driver['licence_number'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_nationality" value="{{ __('Nationality') }}*" />
                                    {{ $participant->driver['nationality'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_email" value="{{ __('E-Mail') }}*" />
                                    {{ $participant->driver['email'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_phone" value="{{ __('Phone number') }}*" />
                                    {{ $participant->driver['phone'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_birth_date" value="{{ __('Birth date') }}*" />
                                    {{ $participant->driver['birth_date'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_birth_place" value="{{ __('Birth place') }}*" />
                                    {{ $participant->driver['birth_place'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_medical_certificate_expiration_date" value="{{ __('Date of expiration of the medical certificate') }}*" />
                                    {{ $participant->driver['medical_certificate_expiration_date'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_residence" value="{{ __('Residence address') }}*" />
                                    {{ $participant->driver['residence_address'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="driver_sex" value="{{ __('driver_sex') }}*" />
                                    {{ $participant->driver['sex'] }}
                                </div>
                                
                            </div>
                        </div>
                </div>
            </div>

            @if ($participant->competitor)
                
            
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
                                    {{ $participant->competitor['first_name'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_last_name" value="{{ __('Surname') }}*" />
                                    {{ $participant->competitor['last_name'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_licence_type" value="{{ __('Licence Type') }}*" />
                                    {{ $participant->competitor['licence_type'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_licence_number" value="{{ __('Licence Number') }}*" />
                                    {{ $participant->competitor['licence_number'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_nationality" value="{{ __('Nationality') }}*" />
                                    {{ $participant->competitor['nationality'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_email" value="{{ __('E-Mail') }}*" />
                                    {{ $participant->competitor['email'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_phone" value="{{ __('Phone number') }}*" />
                                    {{ $participant->competitor['phone'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_birth_date" value="{{ __('Birth date') }}*" />
                                    {{ $participant->competitor['birth_date'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_birth_place" value="{{ __('Birth place') }}*" />
                                    {{ $participant->competitor['birth_place'] }}
                                </div>

                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_residence" value="{{ __('Residence address') }}*" />
                                    {{ $participant->competitor['residence_address'] }}
                                </div>
                                
                            </div>
                        </div>
                </div>
            </div>

            @endif

            @if ($participant->mechanic)
                
            
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
                                    {{ $participant->mechanic['licence_number'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="mechanic_name" value="{{ __('Name and Surname') }}" />
                                    {{ $participant->mechanic['name'] }}
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            @endif

            <x-jet-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-jet-section-title>
                    <x-slot name="title">{{ __('Vehicle') }}</x-slot>
                    <x-slot name="description">{{ __('The characteristics of the vehicle.') }}</x-slot>
                </x-jet-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">

                                @foreach ($participant->vehicles as $vehicle)
                                    
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="vehicle_chassis_manufacturer" value="{{ __('Chassis Manufacturer') }}*" />
                                        {{ $vehicle['chassis_manufacturer'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="vehicle_engine_manufacturer" value="{{ __('Engine Manufacturer') }}*" />
                                        {{ $vehicle['engine_manufacturer'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="vehicle_engine_model" value="{{ __('Engine Model') }}*" />
                                        {{ $vehicle['engine_model'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="vehicle_oil_manufacturer" value="{{ __('Oil Manufacturer') }}*" />
                                        {{ $vehicle['oil_manufacturer'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="vehicle_oil_type" value="{{ __('Oil Type') }}" />
                                        {{ $vehicle['oil_type'] }}
                                    </div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <x-jet-label for="vehicle_oil_percentage" value="{{ __('Oil Percentage') }}*" />
                                        {{ $vehicle['oil_percentage'] }}
                                    </div>
                                @endforeach

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


        </div>
    </div>
</x-app-layout>
