@props(['id' => null])

<div class="col-span-6 sm:col-span-4">
    <x-jet-input-error for="{{ $id }}_address" class="mb-2" />
    <x-jet-input-error for="{{ $id }}_city" class="mb-2" />
    <x-jet-input-error for="{{ $id }}_province" class="mb-2" />
    <x-jet-input-error for="{{ $id }}_postal_code" class="mb-2" />

    <div class="w-full">
        <x-jet-label for="{{ $id }}_address" value="{{ __('address') }}*" />
        <x-jet-input id="{{ $id }}_address" type="text" name="{{ $id }}_address" class="mt-1 block w-full" :value="old($id.'_address')"  />
    </div>
    <div class="w-">
        <x-jet-label for="{{ $id }}_city" value="{{ __('city') }}*" />
        <x-jet-input id="{{ $id }}_city" type="text" name="{{ $id }}_city" class="mt-1 block w-full" :value="old($id.'_city')"  />
    </div>
    <div class="">
        <x-jet-label for="{{ $id }}_province" value="{{ __('province') }}*" />
        <x-jet-input id="{{ $id }}_province" type="text" name="{{ $id }}_province" class="mt-1 block w-full" :value="old($id.'_province')"  />
    </div>
    <div class="">
        <x-jet-label for="{{ $id }}_postal_code" value="{{ __('postal code') }}*" />
        <x-jet-input id="{{ $id }}_postal_code" type="text" name="{{ $id }}_postal_code" class="mt-1 block w-full" :value="old($id.'_postal_code')"  />
    </div>
</div>
