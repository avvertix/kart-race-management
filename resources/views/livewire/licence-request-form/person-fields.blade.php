{{-- The fields of a HolderData bound to $prefix, e.g. "holder" or "parents.0" --}}
@php
    $inputClass = 'mt-1 block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
    $id = str_replace('.', '_', $prefix);
@endphp

<div class="grid grid-cols-6 gap-4">
    <div class="col-span-6 sm:col-span-3">
        <x-label for="{{ $id }}_first_name" value="{{ __('First name') }}*" />
        <x-input id="{{ $id }}_first_name" type="text" wire:model="{{ $prefix }}.first_name" class="mt-1 block w-full" autocomplete="given-name" />
        <x-input-error for="{{ $prefix }}.first_name" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-3">
        <x-label for="{{ $id }}_last_name" value="{{ __('Last name') }}*" />
        <x-input id="{{ $id }}_last_name" type="text" wire:model="{{ $prefix }}.last_name" class="mt-1 block w-full" autocomplete="family-name" />
        <x-input-error for="{{ $prefix }}.last_name" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-2">
        <x-label for="{{ $id }}_sex" value="{{ __('Sex') }}*" />
        <select id="{{ $id }}_sex" wire:model="{{ $prefix }}.sex" class="{{ $inputClass }}">
            <option value="">{{ __('Choose…') }}</option>
            @foreach ([\App\Models\Sex::MALE, \App\Models\Sex::FEMALE] as $sex)
                <option value="{{ $sex->value }}">{{ $sex->localizedName() }}</option>
            @endforeach
        </select>
        <x-input-error for="{{ $prefix }}.sex" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-2">
        <x-label for="{{ $id }}_birth_date" value="{{ __('Birth date') }}*" />
        <x-input id="{{ $id }}_birth_date" type="date" wire:model="{{ $prefix }}.birth_date" class="mt-1 block w-full" />
        <x-input-error for="{{ $prefix }}.birth_date" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-2">
        <x-label for="{{ $id }}_birth_place" value="{{ __('Birth place') }}*" />
        <x-input id="{{ $id }}_birth_place" type="text" wire:model="{{ $prefix }}.birth_place" class="mt-1 block w-full" />
        <x-input-error for="{{ $prefix }}.birth_place" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-3">
        <x-label for="{{ $id }}_nationality" value="{{ __('Nationality') }}*" />
        <x-nationality-input id="{{ $id }}_nationality" name="{{ $id }}_nationality" wire:model="{{ $prefix }}.nationality" class="mt-1 block w-full" />
        <x-input-error for="{{ $prefix }}.nationality" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-3">
        <x-label for="{{ $id }}_fiscal_code" value="{{ __('Fiscal code') }}" />
        <x-input id="{{ $id }}_fiscal_code" type="text" wire:model="{{ $prefix }}.fiscal_code" class="mt-1 block w-full" autocapitalize="characters" />
        <x-input-error for="{{ $prefix }}.fiscal_code" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-3">
        <x-label for="{{ $id }}_email" value="{{ __('E-Mail') }}*" />
        <x-input id="{{ $id }}_email" type="email" wire:model="{{ $prefix }}.email" class="mt-1 block w-full" autocomplete="email" />
        <x-input-error for="{{ $prefix }}.email" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-3">
        <x-label for="{{ $id }}_phone" value="{{ __('Phone number') }}*" />
        <x-input id="{{ $id }}_phone" type="tel" wire:model="{{ $prefix }}.phone" class="mt-1 block w-full" autocomplete="tel" />
        <x-input-error for="{{ $prefix }}.phone" class="mt-1" />
    </div>
    <div class="col-span-6">
        <x-label for="{{ $id }}_address" value="{{ __('Residence address') }}*" />
        <x-input id="{{ $id }}_address" type="text" wire:model="{{ $prefix }}.residence_address.address" class="mt-1 block w-full" autocomplete="street-address" />
        <x-input-error for="{{ $prefix }}.residence_address.address" class="mt-1" />
    </div>
    <div class="col-span-6 sm:col-span-2">
        <x-label for="{{ $id }}_city" value="{{ __('City') }}*" />
        <x-input id="{{ $id }}_city" type="text" wire:model="{{ $prefix }}.residence_address.city" class="mt-1 block w-full" />
        <x-input-error for="{{ $prefix }}.residence_address.city" class="mt-1" />
    </div>
    <div class="col-span-3 sm:col-span-2">
        <x-label for="{{ $id }}_postal_code" value="{{ __('Postal code') }}*" />
        <x-input id="{{ $id }}_postal_code" type="text" wire:model="{{ $prefix }}.residence_address.postal_code" class="mt-1 block w-full" autocomplete="postal-code" />
        <x-input-error for="{{ $prefix }}.residence_address.postal_code" class="mt-1" />
    </div>
    <div class="col-span-3 sm:col-span-2">
        <x-label for="{{ $id }}_province" value="{{ __('Province') }}" />
        <x-input id="{{ $id }}_province" type="text" wire:model="{{ $prefix }}.residence_address.province" class="mt-1 block w-full" />
        <x-input-error for="{{ $prefix }}.residence_address.province" class="mt-1" />
    </div>
</div>
