@props(['id' => null, 'value' => []])

<div class="col-span-6 sm:col-span-4 grid grid-cols-4 gap-2">
    <x-input-error for="{{ $id }}_address" class="mb-2" />
    <x-input-error for="{{ $id }}_city" class="mb-2" />
    <x-input-error for="{{ $id }}_province" class="mb-2" />
    <x-input-error for="{{ $id }}_postal_code" class="mb-2" />

    <div class="col-span-3">
        <x-label for="{{ $id }}_address" value="{{ __('Street') }}*" />
        <x-input id="{{ $id }}_address" type="text" name="{{ $id }}_address" class="mt-1 block w-full" :value="old($id.'_address', $value['address'] ?? null)"  />
    </div>
    <div class="">
        <x-label for="{{ $id }}_postal_code" value="{{ __('Postal code') }}*" />
        <x-input id="{{ $id }}_postal_code" type="text" name="{{ $id }}_postal_code" class="mt-1 block w-full" :value="old($id.'_postal_code', $value['postal_code'] ?? null)"  />
    </div>
    <div class="col-span-3">
        <x-label for="{{ $id }}_city" value="{{ __('City') }}*" />
        <x-input id="{{ $id }}_city" type="text" name="{{ $id }}_city" class="mt-1 block w-full" :value="old($id.'_city', $value['city'] ?? null)"  />
    </div>
    <div class="">
        <x-label for="{{ $id }}_province" value="{{ __('Province') }}" />
        <x-input id="{{ $id }}_province" type="text" name="{{ $id }}_province" class="mt-1 block w-full" :value="old($id.'_province', $value['province'] ?? null)"  />
    </div>
</div>
