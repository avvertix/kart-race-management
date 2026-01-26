@php
    $driver = old('driver', optional($template ?? null)->driver) ?? [];
    $competitor = old('competitor', optional($template ?? null)->competitor) ?? [];
    $mechanic = old('mechanic', optional($template ?? null)->mechanic) ?? [];
    $vehicles = optional($template ?? null)->vehicles;
    $vehicle = $vehicles[0] ?? [];

    // Pre-populate driver email with logged-in user's email if verified and creating new template
    $defaultDriverEmail = $driver['email'] ?? null;
    if (is_null($defaultDriverEmail) && !isset($template) && auth()->user()?->hasVerifiedEmail()) {
        $defaultDriverEmail = auth()->user()->email;
    }
@endphp

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Race Number') }}</x-slot>
        <x-slot name="description">
            {{ __('The race number for this driver.') }}
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="bib" value="{{ __('Race number') }}*" />
                    <x-input id="bib" type="number" name="bib" class="mt-1 block w-full" :value="old('bib', optional($template ?? null)->bib)" min="1" required />
                    <x-input-error for="bib" class="mt-2" />
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
            <em>{{ __('required') }}</em>.
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_first_name" value="{{ __('Name') }}*" />
                    <x-input id="driver_first_name" type="text" name="driver_first_name" class="mt-1 block w-full" :value="old('driver_first_name', $driver['first_name'] ?? null)" required />
                    <x-input-error for="driver_first_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_last_name" value="{{ __('Surname') }}*" />
                    <x-input id="driver_last_name" type="text" name="driver_last_name" class="mt-1 block w-full" :value="old('driver_last_name', $driver['last_name'] ?? null)" required />
                    <x-input-error for="driver_last_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_fiscal_code" value="{{ __('Fiscal code') }}" />
                    <x-input id="driver_fiscal_code" type="text" name="driver_fiscal_code" class="mt-1 block w-full" :value="old('driver_fiscal_code', $driver['fiscal_code'] ?? null)" />
                    <x-input-error for="driver_fiscal_code" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence_type" value="{{ __('Licence Type') }}" />
                    <x-input-error for="driver_licence_type" class="mt-2" />
                    <x-options-selector id="driver_licence_type" name="driver_licence_type" class="mt-1" :value="old('driver_licence_type', $driver['licence_type'] ?? null)" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_licence_number" value="{{ __('Licence Number') }}" />
                    <x-input id="driver_licence_number" type="text" name="driver_licence_number" class="mt-1 block w-full" :value="old('driver_licence_number', $driver['licence_number'] ?? null)" />
                    <x-input-error for="driver_licence_number" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_nationality" value="{{ __('Nationality') }}" />
                    <x-input id="driver_nationality" type="text" name="driver_nationality" class="mt-1 block w-full" :value="old('driver_nationality', $driver['nationality'] ?? null)" />
                    <x-input-error for="driver_nationality" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_email" value="{{ __('E-Mail') }}" />
                    <x-input id="driver_email" type="email" name="driver_email" class="mt-1 block w-full" :value="old('driver_email', $defaultDriverEmail)" />
                    <x-input-error for="driver_email" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_phone" value="{{ __('Phone number') }}" />
                    <x-input id="driver_phone" type="text" name="driver_phone" class="mt-1 block w-full" :value="old('driver_phone', $driver['phone'] ?? null)" />
                    <x-input-error for="driver_phone" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_birth_date" value="{{ __('Birth date') }}" />
                    <p class="text-sm">{{ __('Supported format:') }} <code>{{ trans('date-input.placeholder') }}</code></p>
                    <x-input-date id="driver_birth_date" name="driver_birth_date" class="mt-1 block w-full" :value="old('driver_birth_date', $driver['birth_date'] ?? null)" />
                    <x-input-error for="driver_birth_date" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_birth_place" value="{{ __('Birth place') }}" />
                    <x-input id="driver_birth_place" type="text" name="driver_birth_place" class="mt-1 block w-full" :value="old('driver_birth_place', $driver['birth_place'] ?? null)" />
                    <x-input-error for="driver_birth_place" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_medical_certificate_expiration_date" value="{{ __('Date of expiration of the medical certificate') }}" />
                    <p class="text-sm">{{ __('Supported format:') }} <code>{{ trans('date-input.placeholder') }}</code></p>
                    <x-input-date id="driver_medical_certificate_expiration_date" name="driver_medical_certificate_expiration_date" class="mt-1 block w-full" :value="old('driver_medical_certificate_expiration_date', $driver['medical_certificate_expiration_date'] ?? null)" />
                    <x-input-error for="driver_medical_certificate_expiration_date" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_residence" value="{{ __('Residence address') }}" />
                    <x-input-error for="driver_residence_address" class="mt-2" />
                    <x-address id="driver_residence" type="text" name="driver_residence" class="mt-1 block w-full" :value="$driver['residence_address'] ?? null" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="driver_sex" value="{{ __('Sex') }}" />
                    <x-input-error for="driver_sex" class="mt-2" />
                    <x-options-selector :options="\App\Models\Sex::class" id="driver_sex" name="driver_sex" class="mt-1" :value="old('driver_sex', $driver['sex'] ?? null)" />
                </div>
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
            <em>{{ __('optional') }}</em>.
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_first_name" value="{{ __('Name') }}" />
                    <x-input id="competitor_first_name" type="text" name="competitor_first_name" class="mt-1 block w-full" :value="old('competitor_first_name', $competitor['first_name'] ?? null)" />
                    <x-input-error for="competitor_first_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_last_name" value="{{ __('Surname') }}" />
                    <x-input id="competitor_last_name" type="text" name="competitor_last_name" class="mt-1 block w-full" :value="old('competitor_last_name', $competitor['last_name'] ?? null)" />
                    <x-input-error for="competitor_last_name" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_fiscal_code" value="{{ __('Fiscal Code') }}" />
                    <x-input id="competitor_fiscal_code" type="text" name="competitor_fiscal_code" class="mt-1 block w-full" :value="old('competitor_fiscal_code', $competitor['fiscal_code'] ?? null)" />
                    <x-input-error for="competitor_fiscal_code" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_licence_type" value="{{ __('Licence Type') }}" />
                    <x-input-error for="competitor_licence_type" class="mt-2" />
                    <x-options-selector :options="\App\Models\CompetitorLicence::class" id="competitor_licence_type" name="competitor_licence_type" class="mt-1" :value="old('competitor_licence_type', $competitor['licence_type'] ?? null)" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_licence_number" value="{{ __('Licence Number') }}" />
                    <x-input id="competitor_licence_number" type="text" name="competitor_licence_number" class="mt-1 block w-full" :value="old('competitor_licence_number', $competitor['licence_number'] ?? null)" />
                    <x-input-error for="competitor_licence_number" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_nationality" value="{{ __('Nationality') }}" />
                    <x-input id="competitor_nationality" type="text" name="competitor_nationality" class="mt-1 block w-full" :value="old('competitor_nationality', $competitor['nationality'] ?? null)" />
                    <x-input-error for="competitor_nationality" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_email" value="{{ __('E-Mail') }}" />
                    <x-input id="competitor_email" type="email" name="competitor_email" class="mt-1 block w-full" :value="old('competitor_email', $competitor['email'] ?? null)" />
                    <x-input-error for="competitor_email" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_phone" value="{{ __('Phone number') }}" />
                    <x-input id="competitor_phone" type="text" name="competitor_phone" class="mt-1 block w-full" :value="old('competitor_phone', $competitor['phone'] ?? null)" />
                    <x-input-error for="competitor_phone" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_birth_date" value="{{ __('Birth date') }}" />
                    <p class="text-sm">{{ __('Supported format:') }} <code>{{ trans('date-input.placeholder') }}</code></p>
                    <x-input-date id="competitor_birth_date" name="competitor_birth_date" class="mt-1 block w-full" :value="old('competitor_birth_date', $competitor['birth_date'] ?? null)" />
                    <x-input-error for="competitor_birth_date" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_birth_place" value="{{ __('Birth place') }}" />
                    <x-input id="competitor_birth_place" type="text" name="competitor_birth_place" class="mt-1 block w-full" :value="old('competitor_birth_place', $competitor['birth_place'] ?? null)" />
                    <x-input-error for="competitor_birth_place" class="mt-2" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="competitor_residence" value="{{ __('Residence address') }}" />
                    <x-input-error for="competitor_residence_address" class="mt-2" />
                    <x-address id="competitor_residence" type="text" name="competitor_residence" :value="$competitor['residence_address'] ?? null" class="mt-1 block w-full" />
                </div>
            </div>
        </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Mechanic') }}</x-slot>
        <x-slot name="description">{{ __('The mechanic that will assist the driver during the race.') }}<br/><em>{{ __('optional') }}</em>.</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5">
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
        <x-slot name="title">{{ __('Template Name') }}</x-slot>
        <x-slot name="description">
            {{ __('A name to identify this template.') }}
        </x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="name" value="{{ __('Template Name') }}" />
                    <x-input id="name" type="text" name="name" class="mt-1 block w-full" :value="old('name', optional($template ?? null)->name)" />
                    <x-input-error for="name" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>
